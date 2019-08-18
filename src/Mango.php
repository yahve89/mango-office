<?php
namespace Yahve89\Container;

use Yahve89\Container\MangoApi;

/**
 * Список методов для работы с API mango-office
 *
 * @author Ivan Alexandrov <yahve1989@gmail.com>
 */
class Mango extends MangoApi
{
    /**
     * Уникальный код АТС
     *
     * @var string $apiKey
     */
    private $apiKey = null;

    /**
     * Ключ для создания подписи
     *
     * @var string $apiSalt
     */
    private $apiSalt = null;

    /**
     * @param string $apiKey Уникальный код АТС
     * @param string $apiSalt Ключ для создания подписи
     */
    public function __construct($apiKey, $apiSalt)
    {
        if (empty($apiKey) or empty($apiSalt)) {
            throw new Exception('bad request', 400, null);
        }

        $this->apiKey = $apiKey;
        $this->apiSalt = $apiSalt;
    }

    /**
     * Mетод авторизации для манго
     * @param array $request данные запроса
     * @param string $apiKey Уникальный код АТС
     * @param string $apiSalt Ключ для создания подписи
     * @return array
     */
    private function authParam($request)
    {
        $json = json_encode($request, JSON_UNESCAPED_SLASHES);
        $sign = hash('sha256', $this->apiKey . $json . $this->apiSalt);
        return ['vpbx_api_key' => $this->apiKey,
            'sign' => $sign,
            'json' => $json,
        ];
    }

    /**
     * метод конвертации данных
     * @param string $csvData данные в формате CSV
     * @return array
     */
    private function csvToArray($csvData)
    {
        $lines = str_getcsv($csvData, "\n");

        foreach ($lines as $key => $line) {
            $array = str_getcsv($line, ';');
            $records = array_filter(explode(',', trim($array[0], '[]')));
            $arResult[$key]['records'] = $records;
            $arResult[$key]['start'] = $array[1];
            $arResult[$key]['finish'] = $array[2];
            $arResult[$key]['answer'] = $array[3];
            $arResult[$key]['from_extension'] = $array[4];
            $arResult[$key]['from_number'] = $array[5];
            $arResult[$key]['to_extension'] = $array[6];
            $arResult[$key]['to_number'] = $array[7];
            $arResult[$key]['disconnect_reason'] = $array[8];
            $arResult[$key]['location'] = $array[9];
            $arResult[$key]['line_number'] = $array[10];
            $arResult[$key]['entry_id'] = $array[11];
        }

        return $arResult;
    }

    /**
     * получает список звонков за выбранный период
     * @param string $dateFrom начальная дата
     * @param string $dateTo конечная дата
     * @param integer $extension внутренний номер абонента
     * @return array || null
     */
    public function reportList($dateFrom, $dateTo, $extension = null)
    {
        $request = [];

        if (empty($dateFrom) or empty($dateTo)) {
            throw new Exception('bad request', 400, null);
        }

        if (!empty($extension)) {
            $request['call_party'] = ['extension' => $extension];
        }

        $request['date_from'] = $dateFrom;
        $request['date_to'] = $dateTo;
        $request['fields'] = implode(',', [
            'records',
            'start',
            'finish',
            'answer',
            'from_extension',
            'from_number',
            'to_extension',
            'to_number',
            'disconnect_reason',
            'location',
            'line_number',
            'entry_id',
        ]);

        $init = MangoApi::init();
        $data = $init->setBaseUri('https://app.mango-office.ru')
            ->setPath('/vpbx/stats/request')
            ->setMethod('POST')
            ->setFormParams($this->authParam($request))
            ->execute();
        $response = $data->client->getBody()->getContents();
        $data = $init->setBaseUri('https://app.mango-office.ru')
            ->setPath('/vpbx/stats/result')
            ->setMethod('POST')
            ->setFormParams($this->authParam(json_decode($response)))
            ->execute();
        $csvData = $data->client->getBody()->getContents();

        if ($ResponseJson = json_decode($csvData)) {
            return $ResponseJson;
        }

        if (strlen($csvData) > 0) {
            return $this->csvToArray($csvData);
        }

        return null;
    }

    /**
     * получает запись разговора
     * @param string $recordingId уникальный идентификатор записи
     * @param string $action download | play
     * @return mp3 file | array
     */
    public function downloadAudio($recordingId, $action = 'download')
    {
        if (empty($recordingId)) {
            throw new Exception('bad request', 400, null);
        }

        $init = MangoApi::init();
        $data = $init->setBaseUri('https://app.mango-office.ru')
            ->setPath('/vpbx/queries/recording/post')
            ->setMethod('POST')
            ->setFormParams($this->authParam([
                'recording_id' => $recordingId,
                'action' => $action,
            ]))->execute();
        $recording = $data->client->getBody()->getContents();

        if ($ResponseJson = json_decode($recording)) {
            return $ResponseJson;
        }

        return $recording;
    }

    /**
     * получает список пользовател
     * @param integer $extension внутренний номер абонента
     * @return array
     */
    public function userList($extension = null)
    {
        $param = [];

        if (!empty($extension)) {
            $param = ['extension' => $extension];
        }

        $init = MangoApi::init();
        $data = $init->setBaseUri('https://app.mango-office.ru')
            ->setPath('/vpbx/config/users/request')
            ->setMethod('POST')
            ->setFormParams($this->authParam($param))->execute();
        $response = $data->client->getBody()->getContents();

        return json_decode($response);
    }
}
