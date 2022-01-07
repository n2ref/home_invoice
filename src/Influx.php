<?php
namespace HomeInvoice;

/**
 *
 */
class Influx {

    /**
     * @var array
     */
    private $options = [];


    /**
     * @param array $options
     */
    public function __construct(array $options) {

        $this->options['url']    = $options['url'] ?? '';
        $this->options['token']  = $options['token'] ?? '';
        $this->options['bucket'] = $options['bucket'] ?? '';
        $this->options['org']    = $options['org'] ?? '';
    }


    /**
     * Отправка данных в InfluxDB2
     * @param array $invoice_data
     * @return void
     */
    public function sendInfluxDB2(array $invoice_data) {

        $client = new \InfluxDB2\Client([
            "url"       => $this->options['url'] ?? '',
            "token"     => $this->options['token'] ?? '',
            "bucket"    => $this->options['bucket'] ?? '',
            "org"       => $this->options['org'] ?? '',
            "precision" => \InfluxDB2\Model\WritePrecision::S,
            'tags'      => [
                'payer_name' => mb_strtoupper($invoice_data['payer_name']),
                'address'    => mb_strtolower(preg_replace('~,[\s]кв.*~imu', '', $invoice_data['address'])),
                'apartment'  => mb_strtolower(preg_replace('~.*кв\.~imu', 'кв. ', $invoice_data['address'])),
            ]
        ]);
        $write_api    = $client->createWriteApi();
        $default_time = strtotime("{$invoice_data['year']}-{$invoice_data['month']}-01 00:00:00");

        // Обычные данные
        $fields = [
            ['name' => 'total_accrued',          'title' => 'Итого начислено'],
            ['name' => 'total_price',            'title' => 'Итого к оплате'],
            ['name' => 'cold_water_count',       'title' => 'Показания приборов расхода холодной воды (куб. м)'],
            ['name' => 'cold_water_diff',        'title' => 'Расход холодной воды (куб. м)'],
            ['name' => 'hot_water_count',        'title' => 'Показания приборов расхода горячей воды (куб. м)'],
            ['name' => 'hot_water_diff',         'title' => 'Расход горячей воды (куб. м)'],
            ['name' => 'house_square',           'title' => 'Общая площадь жилых помещений'],
            ['name' => 'house_sub_square',       'title' => 'Площадь вспомогательных помещений'],
            ['name' => 'house_people_energy',    'title' => 'Количество используемых в расчете возмещения расходов по электроэнергии, потребляемой на работу лифтов, зарегистрированных по месту жительства'],
            ['name' => 'house_people_other',     'title' => 'Количество используемых в расчете прочих жилищно-коммунальных услуг зарегистрированных по месту жительства'],
            ['name' => 'house_hot_water_count',  'title' => 'Горячая вода (куб. м)'],
            ['name' => 'house_hot_water_cal',    'title' => 'Горячее водоснабжение (подогрев воды)(Гкал)'],
            ['name' => 'house_cold_water_count', 'title' => 'Холодная вода (куб. м)'],
            ['name' => 'house_energy',           'title' => 'Электроэнергия на освещение и работу оборудования (кВт*ч)'],
            ['name' => 'house_energy_lift',      'title' => 'Электроэнергия на работу лифта (кВт*ч)'],
        ];

        $metrics       = [];
        $metric_fields = [];

        foreach ($fields as $field) {
            if (isset($invoice_data[$field['name']])) {

                $metric_fields[$field['name']] = is_numeric($invoice_data[$field['name']]) ? (float)$invoice_data[$field['name']] : 0.0;
            }
        }
        $metrics[] = [
            'name'   => 'house_invoice',
            'tags'   => ['type' => 'simple'],
            'fields' => $metric_fields,
            'time'   => $default_time,
        ];



        // Услуги
        $services = [];
        if ( ! empty($invoice_data['services'])) {
            foreach ($invoice_data['services'] as $service) {

                if ( ! empty($service['rows'])) {
                    foreach ($service['rows'] as $row) {

                        if ( ! empty($row)) {
                            $row['title'] = trim($row['title']);

                            $services[] = [$this->translit($row['title']) . '_title'         => $row['title']];
                            $services[] = [$this->translit($row['title']) . '_value'         => (float)($row['volume'] ?? 0.0)];
                            $services[] = [$this->translit($row['title']) . '_rate'          => (float)($row['rate'] ?? 0.0)];
                            $services[] = [$this->translit($row['title']) . '_accrued'       => (float)($row['accrued'] ?? 0.0)];
                            $services[] = [$this->translit($row['title']) . '_privileges'    => (float)($row['privileges'] ?? 0.0)];
                            $services[] = [$this->translit($row['title']) . '_recalculation' => (float)($row['recalculation'] ?? 0.0)];
                            $services[] = [$this->translit($row['title']) . '_total'         => (float)($row['total'] ?? 0.0)];
                        }
                    }
                }
            }
        }

        if ( ! empty($services)) {
            foreach ($services as $service) {
                $metrics[] = [
                    'name'   => 'house_invoice',
                    'tags'   => ['type' => 'service'],
                    'fields' => $service,
                    'time'   => $default_time,
                ];
            }
        }


        // Доп услуги
        $extra_services = [];
        if ( ! empty($invoice_data['services_extra'])) {
            foreach ($invoice_data['services_extra'] as $service_extra) {

                if ( ! empty($service_extra)) {
                    $service_extra['title'] = trim($service_extra['title']);

                    $extra_services[] = [$this->translit($service_extra['title']) . '_title' => $service_extra['title']];
                    $extra_services[] = [$this->translit($service_extra['title']) . '_value' => (float)($service_extra['value'] ?? 0.0)];
                }
            }
        }


        if ( ! empty($extra_services)) {
            foreach ($extra_services as $extra_service) {
                $metrics[] = [
                    'name'   => 'house_invoice',
                    'tags'   => ['type' => 'extra_service'],
                    'fields' => $extra_service,
                    'time'   => $default_time,
                ];
            }
        }

        $write_api->write($metrics);
        $write_api->close();
    }


    /**
     * @param $text
     * @return string|string[]|null
     */
    private function translit($text) {

        $replace = [
            "'" => "", "`" => "", " " => "-",
            "а" => "a", "А" => "a", "б" => "b", "Б" => "b",
            "в" => "v", "В" => "v", "г" => "g", "Г" => "g",
            "д" => "d", "Д" => "d", "е" => "e", "Е" => "e",
            "ё" => "e", "Ё" => "e", "ж" => "zh", "Ж" => "zh",
            "з" => "z", "З" => "z", "и" => "i", "И" => "i",
            "й" => "y", "Й" => "y", "к" => "k", "К" => "k",
            "л" => "l", "Л" => "l", "м" => "m", "М" => "m",
            "н" => "n", "Н" => "n", "о" => "o", "О" => "o",
            "п" => "p", "П" => "p", "р" => "r", "Р" => "r",
            "с" => "s", "С" => "s", "т" => "t", "Т" => "t",
            "у" => "u", "У" => "u", "ф" => "f", "Ф" => "f",
            "х" => "h", "Х" => "h", "ц" => "c", "Ц" => "c",
            "ч" => "ch", "Ч" => "ch", "ш" => "sh", "Ш" => "sh",
            "щ" => "sch", "Щ" => "sch", "ъ" => "", "Ъ" => "",
            "ы" => "y", "Ы" => "y", "ь" => "", "Ь" => "",
            "э" => "e", "Э" => "e", "ю" => "yu", "Ю" => "yu",
            "я" => "ya", "Я" => "ya", "і" => "i", "І" => "i",
            "ї" => "yi", "Ї" => "yi", "є" => "e", "Є" => "e"
        ];


        $text = iconv("UTF-8", "UTF-8//IGNORE", strtr($text, $replace));

        return mb_strtolower(preg_replace('~[^a-z0-1\-]*~', '', $text));
    }
}