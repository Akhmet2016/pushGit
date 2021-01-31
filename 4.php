<?php

class ws_getDashboardFundConditionReportAPI extends ws_mobileAPI
{
    function nameAPI()
    {
        $name = 'getDashboardFundConditionReport';
        return $name;

    }

    function urlAPI()
    {
        $web_url = $this->loader->core->getEnvData()['web_url_folder'];
        $url = $web_url . '/mobileAPI/getDashboardFundConditionReport';
        return $url;
    }

    function info()
    {
        $info = 'Описание запроса:<br>Дашборд - Отчет текущего состояния фонда';
        return $info;
    }

    function get($v)
    {
        $data = [];
        $equipArray = [];
        $equipInfo = [];
        $get = $_REQUEST;
        if (fill($get["userId"])) {
            $userId = $get["userId"];
            if (fill($this->model->sel_objv(['s' => 'obj_id', 'f' => ['obj_id' => $userId]]))) {
                $groupId = $this->model->sel_rel_objv(['s' => 'pobj_id1', 'f' => ['obj_id' => $userId, 'rel_type_id' => 66], 'outtype' => 'single']);
                if (fill($groupId)) {
                    $equipAccess = $this->model->sel_cno_armmetr_equip(['s' => 'equip_id', 'f' => ['group_id' => $groupId], 'outtype' => 'single']);
                    if (fill($equipAccess)) {
                        $equipIdArray = explode(",", $equipAccess);
                        $equipIdArray = ws_arrayUnique($equipIdArray);
                        if (fill($equipIdArray)) {
                            $orgCodeArray = $this->model->sel_dictv(['s' => 'code', 'f' => ['dict_code' => 'cno_user_org'], 'out' => 'valuenum', 'outtype' => 'single']);
                            $orgId = $this->model->sel_parv(['s' => 'valuenum', 'f' => ['obj_id' => $groupId, 'par_type_code' => 'cno_groupe_org'], 'outtype' => 'single']);
                            $orgCode = $orgCodeArray[$orgId];
                            if (fill($orgCode)) {
                                if ($orgCode == 'rvp') {
                                    $rezCode = '';
                                } elseif ($orgCode == 'znds') {
                                    $rezCode = '_znds';
                                } elseif ($orgCode == 'zndh' || $orgCode == 'zn') {
                                    $rezCode = '_zndh';
                                }
                            }
                            //Работа
                            $dictValue1 = $this->model->sel_dictv(['s' => 'valuenum', 'f' => ['dict_code' => 'cno_state', 'code' => 'in_work']]);
                            if (fill($dictValue1)) {
                                $equipArray1 = $this->model->sel_parv(
                                    [
                                        's' => 'obj_id',
                                        'f' => [
                                            'par_type_code' => 'cno_state',
                                            'obj_id' => $equipIdArray,
                                            'valuenum' => $dictValue1
                                        ]
                                    ]
                                );
                                $equipArray1 = ws_arrayUnique($equipArray1);
                            }
                            //Фонд ЗИП
                            $cnoFundZip = $this->model->sel_obj(['s' => 'obj_id', 'f' => ['code' => 'cno_fund_ZiP' . $rezCode], 'outtype' => 'single']);
                            if (fill($cnoFundZip)) {
                                $equipArray2 = $this->model->sel_rel(['s' => 'obj_id', 'f' => ['obj_id' => $equipIdArray, 'rel_type_id' => 64, 'pobj_id1' => $cnoFundZip]]);
                                $equipArray2 = ws_arrayUnique($equipArray2);
                            }
                            //Утилизировано
                            $dictValue2 = $this->model->sel_dictv(['s' => 'valuenum', 'f' => ['dict_code' => 'cno_state', 'code' => 'write_off']]);
                            if (fill($dictValue2)) {
                                $equipArray3 = $this->model->sel_parv(
                                    [
                                        's' => 'obj_id',
                                        'f' => [
                                            'par_type_code' => 'cno_state',
                                            'obj_id' => $equipIdArray,
                                            'valuenum' => $dictValue2
                                        ]
                                    ]
                                );
                                $equipArray3 = ws_arrayUnique($equipArray3);
                            }
                            //Вывезено
                            $dictValue3 = $this->model->sel_dictv(['s' => 'valuenum', 'f' => ['dict_code' => 'cno_type_storage_place', 'code' => 'external']]);
                            if (fill($dictValue3)) {
                                $storagePlace = $this->model->sel_parv(
                                    [
                                        's' => 'obj_id',
                                        'f' => [
                                            'par_type_code' => 'cno_type_storage_place',
                                            'valuenum' => $dictValue3
                                        ]
                                    ]
                                );
                                if (fill($storagePlace)) {
                                    $equipArray4 = $this->model->sel_rel(['s' => 'obj_id', 'f' => ['obj_id' => $equipIdArray, 'rel_type_id' => 64, 'pobj_id1' => $storagePlace]]);
                                    $equipArray4 = ws_arrayUnique($equipArray4);
                                }
                            }
                            //Вышел срок возврата
                            $endDate = $this->model->sel_cno_transfer(['f' => ['obj_id' => $equipIdArray, 'timestart' => ['field' => "end_date", 'val' => date('Ymd H:i:s', time()), "operate" => "<"]], 's' => 'obj_id']);
                            $inFactId = $this->model->sel_obj(['s' => 'obj_id', 'f' => ['code' => 'cno_in_fact' . $rezCode], 'outtype' => 'single']);
                            if (fill($endDate)) {
                                $equipArray5 = $this->model->sel_rel(['s' => 'obj_id', 'f' => ['obj_id' => $endDate, 'rel_type_id' => 64, 'pobj_id' => $inFactId]]);
                                $equipArray5 = ws_arrayUnique($equipArray5);
                            }
                        }
                    }
                    if (fill($equipArray1)) $equipArray = array_merge($equipArray, $equipArray1);
                    if (fill($equipArray2)) $equipArray = array_merge($equipArray, $equipArray2);
                    if (fill($equipArray3)) $equipArray = array_merge($equipArray, $equipArray3);
                    if (fill($equipArray4)) $equipArray = array_merge($equipArray, $equipArray4);
                    if (fill($equipArray5)) $equipArray = array_merge($equipArray, $equipArray5);
                    $equipArray = ws_arrayUnique($equipArray);
                    $manufactoryDict = $this->dmodel->getComboDictionItem(['code' => 'cno_org'])['raw'];
                    if (fill($equipArray)) {
                        $parArray = $this->model->sel_parv(
                            [
                                's' => ['valuestr','valuenum'],
                                'f' => [
                                    'obj_id' => $equipArray,
                                    'par_type_code' => [
                                        'cno_model',
                                        'cno_zav_number'
                                    ]
                                ],
                                'out' => [
                                    'obj_id',
                                    'par_type_code'
                                ],
                                'outtype' => 'single'
                            ]);
                        $manufactory = $this->model->sel_parv(['s' => 'valuenum', 'f' => ['obj_id' => $equipArray, 'par_type_code' => 'cno_ceh'], 'out' => 'obj_id', 'outtype' => 'single']);
                        $rel = $this->model->sel_rel_objv(['f' => ['obj_id' => $equipArray, 'rel_type_id' => 64]]);
                        foreach ($rel as $r => $ritem) {
                            $tree[$ritem['obj_id']] = array_reverse($ritem['tree'])[2];
                        }
                        if (fill($tree)) {
                            $names = $this->model->sel_objv(['s' => 'capt', 'f' => ['obj_id' => array_merge($tree, $equipArray)], 'out' => 'obj_id', 'outtype' => 'single']);
                        }
                        if (fill($names)) {
                            foreach ($equipArray as $i => $item) {
                                $equipInfo[$item] = [
                                    'id' => $item,
                                    'name' => $names[$item] ?? '',
                                    'manufactory' => $manufactoryDict[$manufactory[$item]] ?? '',
                                    'zav_number' => $parArray[$item]['cno_zav_number']['valuestr'] ?? ''
                                ];
                            }
                        }
                        if (fill($names)) {
                            if (fill($equipArray1)) {
                                $data['data']['work'] = $this->getDataArray([
                                    'array' => $equipArray1,
                                    'names' => $names,
                                    'pars' => $parArray,
                                    'tree' => $tree,
                                    'equip' => $equipInfo
                                ]);
                            } else {
                                $data['data']['work'] = [];
                            }
                            if (fill($equipArray2)) {
                                $data['data']['zip'] = $this->getDataArray([
                                    'array' => $equipArray2,
                                    'names' => $names,
                                    'pars' => $parArray,
                                    'tree' => $tree,
                                    'equip' => $equipInfo
                                ]);
                            } else {
                                $data['data']['zip'] = [];
                            }
                            if (fill($equipArray3)) {
                                $data['data']['reclaimed'] = $this->getDataArray([
                                    'array' => $equipArray3,
                                    'names' => $names,
                                    'pars' => $parArray,
                                    'tree' => $tree,
                                    'equip' => $equipInfo
                                ]);
                            } else {
                                $data['data']['reclaimed'] = [];
                            }
                            if (fill($equipArray4)) {
                                $data['data']['out'] = $this->getDataArray([
                                    'array' => $equipArray4,
                                    'names' => $names,
                                    'pars' => $parArray,
                                    'tree' => $tree,
                                    'equip' => $equipInfo
                                ]);
                            } else {
                                $data['data']['out'] = [];
                            }
                            if (fill($equipArray5)) {
                                $data['data']['return'] = $this->getDataArray([
                                    'array' => $equipArray5,
                                    'names' => $names,
                                    'pars' => $parArray,
                                    'tree' => $tree,
                                    'equip' => $equipInfo
                                ]);
                            } else {
                                $data['data']['return'] = [];
                            }
                        }
                    }
                } else {
                    $data = [
                        "error" => [
                            "code" => "404",
                            "description" => "Not Found",
                            "message" => "Группа с правами пользователя не обнаружена"
                        ]
                    ];
                }
            } else {
                $data = [
                    "error" => [
                        "code" => "404",
                        "discription" => "Not Found",
                        "message" => "Пользователь не найден"
                    ]
                ];
            }
        } else {
            $data = [
                "error" => [
                    "code" => "400",
                    "discription" => "Bad Request",
                    "message" => "Обязательные поля 'userId', 'month' или 'year' не заполнены"
                ]
            ];
        }
        $this->loader->webapiMobileModel->addLog(['api' => $this->nameAPI(), 'get' => $get, 'data' => $data]);
        return $data;
    }

    function getDataArray($v) {
        $data = [];
        if (fill($v['array']) && fill($v['names']) && fill($v['pars']) && fill($v['tree']) && fill($v['equip'])) {
            $array = $v['array'];
            $names = $v['names'];
            $parArray = $v['pars'];
            $tree = $v['tree'];
            $equipInfo = $v['equip'];
            $count = 0;
            $countArray = [];
            $namesFlag = [];
            $data1 = [];
            foreach ($array as $e => $eitem) {
                if ($namesFlag[$names[$tree[$eitem]]] == 1) {
                    $data1[$countArray[$names[$tree[$eitem]]]]['obj_id_array'][] = $equipInfo[$eitem] ?? '';
                } else {
                    $countArray[$names[$tree[$eitem]]] = $count;
                    $namesFlag[$names[$tree[$eitem]]] = 1;
                    $data1[$countArray[$names[$tree[$eitem]]]]['name'] = $names[$tree[$eitem]] ?? '';
                    $data1[$countArray[$names[$tree[$eitem]]]]['obj_id_array'][] = $equipInfo[$eitem] ?? '';
                    $count++;
                }
            }
            foreach ($data1 as $dataKey => $dataVal) {
                $count = 0;
                $countArray = [];
                $modelFlag = [];
                foreach ($dataVal['obj_id_array'] as $valKey => $valVal) {
                    if (fill($parArray[$valVal['id']]['cno_model']['valuenum'])) {
                        if ($modelFlag[$parArray[$valVal['id']]['cno_model']['valuenum']] == 1) {
                            $data[$dataKey]['name'] = $dataVal['name'];
                            $data[$dataKey]['model_id_array'][$countArray[$parArray[$valVal['id']]['cno_model']['valuenum']]]['name'] = $parArray[$valVal['id']]['cno_model']['valuestr'];
                            $data[$dataKey]['model_id_array'][$countArray[$parArray[$valVal['id']]['cno_model']['valuenum']]]['obj_id_array'][] = $equipInfo[$valVal['id']] ?? '';
                        } else {
                            $countArray[$parArray[$valVal['id']]['cno_model']['valuenum']] = $count;
                            $modelFlag[$parArray[$valVal['id']]['cno_model']['valuenum']] = 1;
                            $data[$dataKey]['name'] = $dataVal['name'];
                            $data[$dataKey]['model_id_array'][$countArray[$parArray[$valVal['id']]['cno_model']['valuenum']]]['name'] = $parArray[$valVal['id']]['cno_model']['valuestr'];
                            $data[$dataKey]['model_id_array'][$countArray[$parArray[$valVal['id']]['cno_model']['valuenum']]]['obj_id_array'][] = $equipInfo[$valVal['id']] ?? '';
                            $count++;
                        }
                    }
                }
            }
        }
        return $data;
    }

    function requiredFields()
    {
        $fields[] = ["name" => 'userId', "type" => "int", "required" => 1];
        return $fields;
    }

    function fields()
    {
        $fields[] = ["name" => 'count', "type" => "int"];
        $fields[] = ["name" => 'id', "type" => "int"];
        $fields[] = ["name" => 'name', "type" => "string"];
        $fields[] = ["name" => 'location', "type" => "string"];
        $fields[] = ["name" => 'manufactory', "type" => "string"];
        $fields[] = ["name" => 'zav_number', "type" => "string"];
        $fields[] = ["name" => 'mest_ust', "type" => "string"];
        $fields[] = ["name" => 'all', "type" => "int"];
        return $fields;
    }

    function getExample()
    {
        $data = [
            "data" => [
                [
                    'work' => [
                        [
                            [
                                'name' => 'СИКН 805',
                                'model_id_array' => [
                                    [
                                        'name' => 'Автоматический выключатель 1П 16А С 4,5кА 230В Easy9 Schneider Electric EZ9F34116',
                                        'obj_id_array' => [
                                            [
                                                'id' => 125666,
                                                'name' => 'Преобразователь температуры РВП - Многоточечный датчик температуры #292643-0001',
                                                'manufactory' => 'ЦППНиГ',
                                                'zav_number' => '12-ТT-4002'
                                            ],
                                            [
                                                'id' => 125666,
                                                'name' => 'Преобразователь температуры РВП - Термопреобразователь сопротивления TR мод. TR40 #31-ZH-0010',
                                                'manufactory' => 'ЦППНиГ',
                                                'zav_number' => '31-ZH-001'
                                            ]
                                        ]
                                    ],
                                    [
                                        'name' => 'Искробезопасный барьер',
                                        'obj_id_array' => [
                                            [
                                                'id' => 209257,
                                                'name' => 'Манометр РВП - Манометр #0024340',
                                                'manufactory' => NULL,
                                                'zav_number' => 0024340
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        return $data;
    }
}
