<?php
namespace HomeInvoice;


/**
 *
 */
class Transform {

    /**
     * @var array
     */
    private $invoice_data = [];


    /**
     * @param array $invoice_data
     */
    public function __construct(array $invoice_data) {

        $this->invoice_data = $invoice_data;
    }


    /**
     * @return array[]
     */
    public function getData(): array {

        $data                   = [];
        $data['simple']         = $this->getSimple();        // Обычные данные
        $data['services']       = $this->getServices();      // Услуги
        $data['services_extra'] = $this->getServicesExtra(); // Доп услуги

        return $data;
    }


    /**
     * Обычные данные
     * @return array[]
     */
    public function getSimple(): array {

        $date_invoice = "{$this->invoice_data['year']}-{$this->invoice_data['month']}-01 00:00:00";

        return [
            'date_invoice'           => ['value' => $date_invoice,                                 'title' => 'Дата извещения'],
            'date_created'           => ['value' => $this->invoice_data['date_created'],           'title' => 'Дата создания'],
            'payer_name'             => ['value' => $this->invoice_data['payer_name'],             'title' => 'Плательщик'],
            'address'                => ['value' => $this->invoice_data['address'],                'title' => 'Адрес помещения'],
            'personal_account'       => ['value' => $this->invoice_data['personal_account'],       'title' => 'Лицевой счет'],
            'total_accrued'          => ['value' => $this->invoice_data['total_accrued'],          'title' => 'Итого начислено'],
            'total_price'            => ['value' => $this->invoice_data['total_price'],            'title' => 'Итого к оплате'],
            'cold_water_count'       => ['value' => $this->invoice_data['cold_water_count'],       'title' => 'Показания приборов расхода холодной воды (куб. м)'],
            'cold_water_diff'        => ['value' => $this->invoice_data['cold_water_diff'],        'title' => 'Расход холодной воды (куб. м)'],
            'hot_water_count'        => ['value' => $this->invoice_data['hot_water_count'],        'title' => 'Показания приборов расхода горячей воды (куб. м)'],
            'hot_water_diff'         => ['value' => $this->invoice_data['hot_water_diff'],         'title' => 'Расход горячей воды (куб. м)'],
            'house_square'           => ['value' => $this->invoice_data['house_square'],           'title' => 'Общая площадь жилых помещений'],
            'house_sub_square'       => ['value' => $this->invoice_data['house_sub_square'],       'title' => 'Площадь вспомогательных помещений'],
            'house_people'           => ['value' => $this->invoice_data['house_people'],           'title' => ''],
            'house_people_energy'    => ['value' => $this->invoice_data['house_people_energy'],    'title' => 'Количество используемых в расчете возмещения расходов по электроэнергии, потребляемой на работу лифтов, зарегистрированных по месту жительства'],
            'house_people_other'     => ['value' => $this->invoice_data['house_people_other'],     'title' => 'Количество используемых в расчете прочих жилищно-коммунальных услуг зарегистрированных по месту жительства'],
            'house_hot_water_count'  => ['value' => $this->invoice_data['house_hot_water_count'],  'title' => 'Горячая вода (куб. м)'],
            'house_hot_water_cal'    => ['value' => $this->invoice_data['house_hot_water_cal'],    'title' => 'Горячее водоснабжение (подогрев воды)(Гкал)'],
            'house_cold_water_count' => ['value' => $this->invoice_data['house_cold_water_count'], 'title' => 'Холодная вода (куб. м)'],
            'house_energy'           => ['value' => $this->invoice_data['house_energy'],           'title' => 'Электроэнергия на освещение и работу оборудования (кВт*ч)'],
            'house_energy_lift'      => ['value' => $this->invoice_data['house_energy_lift'],      'title' => 'Электроэнергия на работу лифта (кВт*ч)'],
        ];
    }


    /**
     * Услуги
     * @return array
     */
    public function getServices(): array {

        $services = [];
        if ( ! empty($this->invoice_data['services'])) {
            foreach ($this->invoice_data['services'] as $service) {

                if ( ! empty($service['rows'])) {
                    foreach ($service['rows'] as $row) {

                        if ( ! empty($row)) {
                            $services[] = [
                                'title'         => trim($row['title']),
                                'unit'          => $row['unit'],
                                'volume'        => (float)($row['volume'] ?? 0.0),
                                'rate'          => (float)($row['rate'] ?? 0.0),
                                'accrued'       => (float)($row['accrued'] ?? 0.0),
                                'privileges'    => (float)($row['privileges'] ?? 0.0),
                                'recalculation' => (float)($row['recalculation'] ?? 0.0),
                                'total'         => (float)($row['total'] ?? 0.0),
                            ];
                        }
                    }
                }
            }
        }

        return $services;
    }


    /**
     * Доп услуги
     * @return array
     */
    public function getServicesExtra(): array {

        $services_extra = [];
        if ( ! empty($this->invoice_data['services_extra'])) {
            foreach ($this->invoice_data['services_extra'] as $service_extra) {

                if ( ! empty($service_extra)) {
                    $service_extra['title'] = trim($service_extra['title']);

                    $services_extra[] = [
                        'title' => trim($service_extra['title']),
                        'value' => (float)($service_extra['value'] ?? 0.0),
                    ];
                }
            }
        }

        return $services_extra;
    }
}
