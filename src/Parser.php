<?php
namespace HomeInvoice;

/**
 *
 */
class Parser {

    /**
     * @var null
     */
    private $pdf_content = null;

    /**
     * @var int[]
     */
    private $moths = [
        'январь'   => 1,
        'февраль'  => 2,
        'март'     => 3,
        'апрель'   => 4,
        'май'      => 5,
        'июнь'     => 6,
        'июль'     => 7,
        'август'   => 8,
        'сентябрь' => 9,
        'октябрь'  => 10,
        'ноябрь'   => 11,
        'декабрь'  => 12,
    ];


    /**
     * @param $pdf_content
     */
    public function __construct($pdf_content) {

        $this->pdf_content = $pdf_content;
    }


    /**
     * Получение текста из PDF файла
     * @return string
     * @throws \Exception
     */
    public function getText(): string {

        $parser = new \Smalot\PdfParser\Parser();
        return $parser->parseContent($this->pdf_content)->getText();
    }


    /**
     * Получение данных из текста
     * @param string $text
     * @return array
     */
    public function getData(string $text): array {

        $data = [];

        // Дата извещения
        $date = $this->getDateNotification($text);
        $data['month_name'] = $date['month_name'];
        $data['month']      = $date['month'];
        $data['year']       = $date['year'];


        $data['date_created']     = $this->getDateCreated($text);     // Дата создания
        $data['payer_name']       = $this->getPayerName($text);       // Плательщик
        $data['address']          = $this->getAddress($text);         // Адрес помещения
        $data['personal_account'] = $this->getPersonalAccount($text); // Лицевой счет
        $data['total_accrued']    = $this->getTotalAccrued($text);    // Итого начислено
        $data['total_price']      = $this->getTotalPrice($text);      // К оплате
        $data['services']         = $this->getServices($text);        // Основная таблица услуг
        $data['services_extra']   = $this->getServicesExtra($text);   // Доп таблица услуг
        $data['cold_water_count'] = $this->getColdWaterCount($text);  // Показания по холодной воде
        $data['cold_water_diff']  = $this->getColdWaterDiff($text);   // Показания по холодной воде
        $data['hot_water_count']  = $this->getHotWaterCount($text);   // Показания по горячей воде
        $data['hot_water_diff']   = $this->getHotWaterDiff($text);    // Показания по горячей воде

        // Показания по дому
        $house = $this->getHouse($text);
        $data['house_square']           = $house['house_square'];
        $data['house_sub_square']       = $house['house_sub_square'];
        $data['house_people']           = $house['house_people'];
        $data['house_people_energy']    = $house['house_people_energy'];
        $data['house_people_other']     = $house['house_people_other'];
        $data['house_hot_water_count']  = $house['house_hot_water_count'];
        $data['house_hot_water_cal']    = $house['house_hot_water_cal'];
        $data['house_cold_water_count'] = $house['house_cold_water_count'];
        $data['house_energy']           = $house['house_energy'];
        $data['house_energy_lift']      = $house['house_energy_lift'];


        return $data;
    }


    /**
     * Дата извещения
     * @param string $text
     * @return void
     */
    public function getDateNotification(string $text): array {

        $data = [];

        preg_match('~ИЗВЕЩЕНИЕ[\s]+за[\s]+([а-я]+)[\s]+(\d+)~imu', $text, $matches);

        $data['month_name'] = $matches[1] ?? '';
        $data['month']      = isset($matches[1]) ? (isset($this->moths[$matches[1]]) ? $this->moths[$matches[1]] : '') : '';
        $data['year']       = $matches[2] ?? '';

        return $data;
    }


    /**
     * Адрес помещения
     * @param string $text
     * @return string
     */
    public function getAddress(string $text): string {

        preg_match('~Адрес\s*помещения\s*:\s*(.*)Лицевой\s*счет~ismU', $text, $matches);
        $address = isset($matches[1]) ? trim($matches[1]) : '';
        if ($address) {
            $address = mb_strtolower($address);
            $address = preg_replace('~\n~', ' ', $address);
            $address = preg_replace('~\s{2,}~', ' ', $address);
            $address = preg_replace('~\s?([\.,])[ ]?~', '$1 ', $address);
            $address = preg_replace('~(\s*)(г|ул|д|кв)\s*\.\s*~si', '$1$2. ', $address);
        }

        return $address;
    }


    /**
     * Плательщик
     * @param string $text
     * @return string
     */
    public function getPayerName(string $text): string {

        preg_match('~Плательщик\s*:\s*(.*)Адрес\s*помещения~ismU', $text, $matches);
        $payer_name = isset($matches[1]) ? trim($matches[1]) : false;
        if ($payer_name) {
            $payer_name = preg_replace('~\s{2,}~', ' ', $payer_name);
        }

        return $payer_name;
    }


    /**
     * Лицевой счет
     * @param string $text
     * @return string
     */
    public function getPersonalAccount(string $text): string {

        preg_match('~Лицевой\s*счет\s*:\s*(\d*)~imu', $text, $matches);
        return $matches[1] ?? '';
    }


    /**
     * Дата создания
     * @param string $text
     * @return string
     */
    public function getDateCreated(string $text): string {

        preg_match('~от[\s]([\d]+\.[\d]+\.[\d]+ [\d]+:[\d]+:[\d]+)~imu', $text, $matches);
        return $matches[1] ?? '';
    }


    /**
     * Итого начислено
     * @param string $text
     * @return string
     */
    public function getTotalAccrued(string $text): string {

        preg_match('~Всего\s*начислено\s*[\d\.]+\s*[\d\.\-]+\s*[\d\.\-]+\s*([\d\.]+)~muis', $text, $matches);
        return $matches[1] ?? '';
    }


    /**
     * Итого к оплате
     * @param string $text
     * @return string
     */
    public function getTotalPrice(string $text): string {

        preg_match('~К[\s]+ОПЛАТЕ[\s]+([\d\.]+)[\s]+~imu', $text, $matches);
        return $matches[1] ?? '';
    }


    /**
     * Основная таблица услуг
     * @param string $text
     * @return array
     */
    public function getServices(string $text): array {

        preg_match('~№\s*п/п.+Итого\s*сумма\s*\(\s*рублей\s*\)\s*(.*)Справочно:~imusU', $text, $matches);
        $text_table = isset($matches[1]) ? $matches[1] : false;

        preg_match_all('~([А-я \(\)\-]*):[А-я,\s]*\d+.*?Итого\s*за~imus', $text_table, $matches);


        $services = [];

        if ( ! empty($matches)) {
            $cells_name = [
                'num',
                'title',
                'unit',
                'volume',
                'rate',
                'accrued',
                'privileges',
                'recalculation',
                'total',
            ];
            foreach ($matches[1] as $group_title) {

                preg_match('~' . preg_quote($group_title) . ':[А-я,\s]*?(.*)Итого[\s]за~imusU', $text_table, $group_matches);

                if (mb_strpos(trim($group_matches[1]), "\t")) {
                    $group_cells = explode("\t", trim($group_matches[1]));
                    $group_rows  = [];

                    $i = 0;
                    $j = 0;
                    foreach ($group_cells as $key => $group_cell) {
                        $i++;

                        $group_cell = preg_replace('~\n~', ' ', $group_cell);
                        $group_cell = preg_replace('~\s{2,}~', ' ', $group_cell);
                        $group_rows[$j][$cells_name[$i-1]] = trim($group_cell);

                        if ($i == 9) {
                            $i = 0;
                            $j++;
                        }
                    }

                    $services[] = [
                        'group_title' => $group_title,
                        'rows'        => $group_rows,
                    ];

                } else {
                    preg_match_all('~(^\d+)\s*([А-я][А-яA-z\(\)\s\-_0-9,\.;]+)(кв\.\s*м|Гкал|куб\.\s*м|чел\.)\s*([\d\.]+)\s+([\d\.]+)\s+([\d\.]+)\s+([\d\.]+)\s+([\d\.]+)\s+([\d\.]+)\s+~imuUs', trim($group_matches[1]), $subgroup_matches);

                    $group_rows = [];
                    $i          = 1;

                    if ( ! empty($subgroup_matches)) {
                        foreach ($subgroup_matches as $key => $subgroup_match) {
                            if ($key == 0) {
                                continue;
                            }

                            foreach ($subgroup_match as $col_num => $item) {
                                $item = preg_replace('~\n~', ' ', $item);
                                $item = preg_replace('~\s{2,}~', ' ', $item);
                                $group_rows[$col_num][$cells_name[$i-1]] = $item;
                            }

                            $i++;
                        }
                    }

                    $services[] = [
                        'group_title' => $group_title,
                        'rows'        => $group_rows,
                    ];
                }
            }
        }

        return $services;
    }


    /**
     * Доп таблица услуг
     * @param string $text
     * @return array
     */
    public function getServicesExtra(string $text): array {

        preg_match('~Итого\s*начислено\s*[\d\.]+(.*)К\s*ОПЛАТЕ~imus', $text, $matches);
        $text_table = $matches[1];

        $services   = [];
        $cells_name = [
            'title',
            'value',
        ];

        preg_match_all('~([А-яA-z\(\)\s\-_0-9,\.;]+)\s*([\d]+\.[\d]+)\s*$~imuUs', trim($text_table), $group_cells);
        $i = 1;
        if ( ! empty($group_cells)) {
            foreach ($group_cells as $key => $group_cell) {
                if ($key == 0) {
                    continue;
                }

                foreach ($group_cell as $col_num => $item) {
                    $item = preg_replace('~\n~', ' ', $item);
                    $item = preg_replace('~\s{2,}~', ' ', $item);
                    $services[$col_num][$cells_name[$i-1]] = trim($item);
                }

                $i++;
            }

            // Названия и значения могут быть перепутаны
            if ( ! empty($services)) {
                foreach ($services as $key => $service_extra) {
                    if (preg_match('~^([\d]+\.[\d]+)\s*(.*)~', $service_extra['title'], $match)) {
                        $services[$key]['title'] = $match[2] . $service_extra['value'];
                        $services[$key]['value'] = $match[1];
                    }
                }
            }
        }

        return $services;
    }


    /**
     * Показания по холодной воде
     * @param string $text
     * @return string
     */
    public function getColdWaterCount(string $text): string {

        preg_match('~Показания\s*приборов\s*индивидуального\s*учета\s*расхода\s*холодной\s*воды\s*на\s*конец\s*отчетного\s*месяца\s*\(куб\.\s*м\)\s*([\d\.]+)~imus', $text, $matches);
        return $matches[1] ?? '';
    }


    /**
     * Показания по холодной воде
     * @param string $text
     * @return string
     */
    public function getColdWaterDiff(string $text): string {

        preg_match('~Расход\s*холодной\s*воды\s*по\s*показаниям\s*приборов\s*индивидуального\s*учета\s*за\s*отчетный\s*месяц\s*\(куб\.\s*м\)\s*([\d\.]+)~imus', $text, $matches);
        return $matches[1] ?? '';
    }


    /**
     * Показания по горячей воде
     * @param string $text
     * @return string
     */
    public function getHotWaterCount(string $text): string {

        preg_match('~Показания\s*приборов\s*индивидуального\s*учета\s*расхода\s*горячей\s*воды\s*на\s*конец\s*отчетного\s*месяца\s*\(куб\.\s*м\)\s*([\d\.]+)~imus', $text, $matches);
        return $matches[1] ?? '';
    }


    /**
     * Показания по горячей воде
     * @param string $text
     * @return string
     */
    public function getHotWaterDiff(string $text): string {

        preg_match('~Расход\s*горячей\s*воды\s*по\s*показаниям\s*приборов\s*индивидуального\s*учета\s*за\s*отчетный\s*месяц\s*\(куб\.\s*м\)\s*([\d\.]+)~imus', $text, $matches);
        return $matches[1] ?? '';
    }


    /**
     * Показания по дому
     * @param string $text
     * @return array
     */
    public function getHouse(string $text): array {

        $data = [];

        // Справочная информация
        preg_match('~Общая\s*площадь\s*жилых\s*помещений:\s*([\d\.]+)~imus', $text, $matches);
        $data['house_square'] = $matches[1] ?? '';

        preg_match('~Площадь\s*вспомогательных\s*помещений:\s*([\d\.]+)~imus', $text, $matches);
        $data['house_sub_square'] = $matches[1] ?? '';

        preg_match('~Количество\s*зарегистрированных\s*по\s*месту\s*жительства:\s*([\d\.]+)~imus', $text, $matches);
        $data['house_people'] = $matches[1] ?? '';

        preg_match('~Количество\s*используемых\s*в\s*расчете\s*возмещения\s*расходов\s*по\s*электроэнергии,\s*потребляемой\s*на\s*работу\s*лифтов,\s*зарегистрированных\s*по\s*месту\s*жительства:\s*([\d\.]+)~imus', $text, $matches);
        if ( ! isset($matches[1])) {
            preg_match('~Количество\s*человек,\s*участвующих\s*в\s*расчете\s*возмещения\s*расходов\s*по\s*электроэнергии,\s*потребляемой\s*на\s*работу\s*лифтов:\s*([\d\.]+)~imus', $text, $matches);
        }
        $data['house_people_energy'] = $matches[1] ?? '';

        preg_match('~Количество\s*используемых\s*в\s*расчете\s*прочих\s*жилищно\-коммунальных\s*услуг\s*зарегистрированных\s*по\s*месту\s*жительства:\s*([\d\.]+)~imus', $text, $matches);
        $data['house_people_other'] = $matches[1] ?? '';

        // ОБЩЕДОМОВОЙ РАСХОД
        preg_match('~Горячая\s*вода\s*\(куб\.\s*м\)\s*([\d\.]+)~imus', $text, $matches);
        $data['house_hot_water_count'] = $matches[1] ?? '';

        preg_match('~Горячее\s*водоснабжение\s*\(подогрев воды\)\s*\(Гкал\)\s*([\d\.]+)~imus', $text, $matches);
        $data['house_hot_water_cal'] = $matches[1] ?? '';

        preg_match('~Холодная\s*вода\s*\(куб\.\s*м\)\s*([\d\.]+)~imus', $text, $matches);
        $data['house_cold_water_count'] = $matches[1] ?? '';

        preg_match('~Электроэнергия,\s*потребляемая\s*на\s*освещение\s*вспомогательных\s*помещений\s*и\s*работу\s*оборудования,\s*за\s*исключением\s*лифтов\s*\(кВт\*ч\)\s*([\d\.]+)~imus', $text, $matches);
        $data['house_energy'] = $matches[1] ?? '';

        preg_match('~Электроэнергия,\s*потребляемая\s*на\s*работу\s*лифта\s*\(кВт\*ч\)\s*([\d\.]+)~imus', $text, $matches);
        $data['house_energy_lift'] = $matches[1] ?? '';

        return $data;
    }
}
