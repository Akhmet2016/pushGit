<?php
require_once('./lib/PHPMailer/PHPMailerAutoload.php');

class ws_cnoPanel_createRequestPanel extends ws_abstractForm
{
    function getPanelTitle($v)
    {
        if (fill($v['id'])) {
            return $this->t("Заявка");
        } else {
            return $this->t("Форма создания заявки");
        }
    }

    function getPanelIcon()
    {
        return "ws_icon_parameter";
    }

    function getWsData($v)
    {
        $m = ['obj_id' => $v['obj_id']];
        return $m;
    }

    function getPanelId($v)
    {
        return $this->getModuleCode() . "_" . $this->getPanelCode();
    }

    function getHW($v)
    {
        $request = $this->model->sel_cno_request_unscheduled(['f' => ['id_request' => $v['id']], 'outtype' => 'single']);
        $userId = $this->loader->core->getCacheData('user_id');
        if (fill($v['type'])) {
            $minusHeight = 0;
            if ($v['type'] != 'mi' && $v['type'] != 'hal' && $v['type'] != 'eq') $minusHeight = 190;
        } else {
            $groupObj = $this->model->sel_objV(['s' => 'groupe', 'f' => ['obj_id' => $request['equip_id']], 'outtype' => 'single']);
            $groupObj != 8 ? $minusHeight = 190 : $minusHeight = 0;
            $minusHeight = ($groupObj != 8 && $groupObj != 7) ? $minusHeight = 190 : $minusHeight = 0;
            if ($v['without_eq'] == true) $minusHeight = 290;
        }
        if (fill($v['id']) && $request['status'] != 5) {
            if (fill($v['id']) && $request['status'] == 4) {
                if ($request['sender_id'] == $userId) {
                    $rezHW = ["width" => 800, "height" => (975 - $minusHeight)];
                } else {
                    $rezHW = ["width" => 800, "height" => (860 - $minusHeight)];
                }
            } else {
                if ($request['status'] == 3) {
                    $rezHW = ($request['sender_id'] != $userId) ? ["width" => 800, "height" => (980 - $minusHeight)] : ["width" => 800, "height" => (860 - $minusHeight)];
                } else {
                    $rezHW = ["width" => 800, "height" => (860 - $minusHeight)];
                }
            }
        } elseif (fill($v['id']) && $request['status'] == 5) {
            if ($request['sender_id'] == $userId) {
                $rezHW = ["width" => 800, "height" => (740 - $minusHeight)];
            } else {
                $rezHW = ["width" => 800, "height" => (935 - $minusHeight)];
            }
        } else {
            $rezHW = ["width" => 800, "height" => (950 - $minusHeight)];
        }
        return $rezHW;
    }

    function getButtonsStore($v)
    {
        $request = $this->model->sel_cno_request_unscheduled(['f' => ['id_request' => $v['id']], 'outtype' => 'single']);
        $accept = ["text" => $this->t("Принять"), "margin" => '0 10 10 0', "handler" => "function() {ws_panelFunc(this,'toAccept',val);}"];
        $dismiss = ["text" => $this->t("Отклонить"), "margin" => '0 10 10 0', "handler" => "function () {ws_panelFunc(this,'toDismiss',val);}"];
        $reject = ["text" => $this->t("Отклонить"), "margin" => '0 10 10 0', "handler" => "function () {ws_panelFunc(this,'reject',val);}"];
        $execute = ["text" => $this->t("Выполнить"), "margin" => '0 10 10 0', "handler" => "function() {ws_panelFunc(this,'execute',val);}"];
        $report = ["text" => $this->t("Сформировать"), "margin" => '0 10 10 0', "handler" => "function() {ws_panelFunc(this,'report',val);}"];
        $closeRequest = ["text" => $this->t("Закрыть заявку"), "margin" => '0 10 10 0', "handler" => "function () {ws_panelFunc(this,'closeRequest',val);}"];
        $resend = ["text" => $this->t("Переотправить"), "margin" => '0 10 10 0', "handler" => "function () {ws_panelFunc(this,'resend',val);}"];
        $close = ["text" => $this->t("Закрыть"), "margin" => '0 10 10 0', "handler" => "function () {ws_panelFunc(this,'closePanel',val);}"];
        $cancel = ["text" => $this->t("Отмена"), "margin" => '0 10 10 0', "handler" => "function () {ws_panelFunc(this,'closePanel',val);}"];
        $userId = $this->loader->core->getCacheData('user_id');
        $status = $request['status'];
        $executorId = $request['executor_id'];
        $senderId = $request['sender_id'];
        $requestId = $v['id'];
        if (fill($requestId) && ($status == 1 || $status == 2)) {
            if ($userId == $executorId) {
                $m['save'] = $accept;
                $m['close'] = $dismiss;
            } else {
                $m['close'] = $close;
            }
        } elseif (fill($requestId) && $status == 3) {
            if ($userId == $executorId) {
                $m['reject'] = $reject;
                $m['save'] = $execute;
                $m['close'] = $cancel;
            } else {
                $m['close'] = $close;
            }
        } elseif (fill($requestId) && $status == 4) {
            if ($userId == $senderId) {
                $m['save'] = $closeRequest;
                $m['close'] = $cancel;
            } else {
                $m['close'] = $close;
            }
        } elseif (fill($requestId) && $status == 5) {
            if ($request['sender_id'] == $userId) {
                $m['close'] = $close;
            } else {
                $m['save'] = $resend;
                $m['close'] = $closeRequest;
            }
        } elseif (fill($requestId) && $status == 6) {
            $m['close'] = $close;
        } else {
            $m['save'] = $report;
            $m['close'] = $cancel;
        }
        return $m;
    }

    function getButtonsList()
    {
        return ['save', 'reject', 'close'];
    }

    function getField($v)
    {
        if (fill($v['id'])) {
            $request = $this->model->sel_cno_request_unscheduled(['f' => ['id_request' => $v['id']], 'outtype' => 'single']);
            if (fill($request)) {
                $eqId = $request['equip_id'];
                $userId = $request['application_creator'];
                $nowUserId = $this->loader->core->getCacheData('user_id');
                $comment = $request['comment'];
                $discipline = $request['discipline'];
                $opKey = $request['operation_type'];
                if ($request['status'] == 1 && $request['executor_id'] == $nowUserId) {
                    if ($this->model->upd_cno_request_unscheduled(['f' => ['id_request' => $v['id']], 'status' => 2]))
                        $this->model->add_cno_requests_hist([
                            'request_id' => $v['id'],
                            'user_id' => $request['executor_id'],
                            'comment' => $request['comment'],
                            'datetime' => date('Ymd',time()),
                            'status' => 2
                        ]);
                }
            }
        } else {
            $eqId = $v['obj_id'];
        }
        if (fill($eqId)) {
            $operTypeGroupe = $this->smodel->getPprOperType()['groupe'];
            $groupObj = $this->model->sel_objV(['s' => 'groupe', 'f' => ['obj_id' => $eqId], 'outtype' => 'single']);
            switch ($groupObj) {
                case 7:
                    $v['type'] = 'eq';
                    break;
                case 8:
                    $v['type'] = 'mi';
                    break;
                default:
                    $v['type'] = 'dir';
            }
            switch ($groupObj) {
                case 7:
                    $operTypes = [
                        'to',
                        'rem',
                        'teh',
                        'rev',
                        'quantity'
                    ];
                    break;
                case 8:
                    $operTypes = [
                        'amto',
                        'amvc'
                    ];
                    break;
                default:
                    $operTypes = [];
            }
            foreach ($operTypes as $typeGroupe) {
                foreach ($operTypeGroupe[$typeGroupe] as $operVal)
                    $operData[$operVal['valuenum']] = $operVal['name'];
            }
        }
        if (!fill($userId))
            $userId = $this->loader->core->getCacheData('user_id');
        $groupeId = $this->smodel->getCnoGroupe()['groupe_id'];
        if (!fill($discipline)) {
            $discGroupePar = $this->model->sel_parV(['f' => ['obj_id' => $groupeId, 'par_type_code' => 'cno_group_discipline'], 's' => 'valuenum', 'outtype' => 'single']);
            $discipline = (fill($discGroupePar)) ? $discGroupePar : 5;
        }
        $groupeOrg = $this->model->sel_parV(['f' => ['obj_id' => $groupeId, 'par_type_code' => 'cno_groupe_org'], 's' => 'valuenum', 'outtype' => 'single']);
        $executors = $this->getLoadExecutors(['discipline' => $discipline, 'groupe_org' => $groupeOrg])['data'];
        foreach ($executors as $item)
            $executorData[$item['id']] = $item['capt'];
        asort($executorData);
        $operData[1000] = 'Прочие внеплановые работы';
        $relTypeId = $this->smodel->getRelTypeId();
        if (fill($eqId)) {
            $parentId = $this->model->sel_rel(['f' => ['obj_id' => $eqId, 'rel_type_id' => $relTypeId], 'outtype' => 'single'])['tree'];
            $parentId = array_reverse($parentId);
            $nameParent = null;
            $parentTree = ($v['type'] == 'dir' && count($parentId) < 5) ? [$parentId[(count($parentId)-3)],$parentId[(count($parentId)-2)]] : [$parentId[2],$parentId[3]];
            $nameParentMas = $this->model->sel_objV(['s' => 'capt', 'f' => ['obj_id' => $parentTree], 'out' => 'obj_id', 'outtype' => 'single']);
            foreach ($parentTree as $pId) {
                $nameParent .= $nameParentMas[$pId] . ' -> ';
            }
            $nameParent = substr($nameParent,0,strlen($nameParent) - 4);
            $eqPar = $this->model->sel_parV(['f' => ['obj_id' => $eqId], 'out' => 'par_type_code', 'outtype' => 'single']);
            $objName = $this->model->sel_obj(['s' => 'name', 'f' => ['obj_id' => $eqId], 'outtype' => 'single']);
        }
        $userPar = $this->model->sel_parV(['f' => ['obj_id' => $userId], 'out' => 'par_type_code', 'outtype' => 'single']);
        $org_name = $this->dmodel->getComboDictionItem(['code' => 'cno_user_org'])['data'];
        $disciplineCombo = $this->model->sel_dictV(['f' => ['dict_code' => 'cno_discipline_type'], 'out' => 'valuenum', 's' => 'name']);
        $class_name = $this->getPanelId($v);
        if ($v['type'] != 'mi' && $v['type'] != 'hal' && $v['type'] != 'eq') {
            if (fill($eqId))
                $ans1 = [
                    ["xtype" => "fieldset", "text" => 'Сведение о "Заказчике"',
                        "items" => [
                            ["xtype" => "textfield", "name" => "location", "text" => 'Место проведения работ', 'labelWidth' => 180, "value" => $nameParent, "editable" => false],
                            ["xtype" => "textfield", "name" => "fio", "text" => 'ФИО подающего заявки лица/№ телефона', 'labelWidth' => 180, "value" => $userPar['user_fam']['valuestr'] . '; тел: ' . $userPar['user_telephone']['valuestr'], "editable" => false],
                        ]
                    ],
                    ["xtype" => "fieldset", "text" => 'Сведение об объекте',
                        "items" => [
                            ["xtype" => "textfield", "name" => "name_eq", "text" => 'Наименование объекта', 'labelWidth' => 180, "value" => $objName, "editable" => false],
                        ]
                    ]
                ];
            else
                $ans1 = [
                    ["xtype" => "fieldset", "text" => 'Сведение о "Заказчике"',
                        "items" => [
                            ["xtype" => "textfield", "name" => "fio", "text" => 'ФИО подающего заявки лица/№ телефона', 'labelWidth' => 180, "value" => $userPar['user_fam']['valuestr'] . '; тел: ' . $userPar['user_telephone']['valuestr'], "editable" => false],
                        ]
                    ]
                ];
        } else {
            $ans1 = [
                ["xtype" => "fieldset", "text" => 'Сведение о "Заказчике"',
                    "items" => [
                        ["xtype" => "textfield", "name" => "location", "text" => 'Место проведения работ', 'labelWidth' => 180, "value" => $nameParent, "editable" => false],
                        ["xtype" => "textfield", "name" => "fio", "text" => 'ФИО подающего заявки лица/№ телефона', 'labelWidth' => 180, "value" => $userPar['user_fam']['valuestr'] . '; тел: ' . $userPar['user_telephone']['valuestr'], "editable" => false],
                    ]
                ],
                ["xtype" => "fieldset", "text" => 'Сведение об оборудовании',
                    "items" => [
                        ["xtype" => "textfield", "name" => "name_eq", "text" => 'Наименование оборудования/СИ, модель', 'labelWidth' => 180, "value" => $eqPar['cno_model']['valuestr'], "editable" => false],
                        ["xtype" => "textfield", "name" => "serial_number", "text" => 'Серийный номер', 'labelWidth' => 180, "value" => $eqPar['cno_zav_number']['valuestr'], "editable" => false],
                        ["xtype" => "textfield", "name" => "issue_year", "text" => 'Год выпуска', 'labelWidth' => 180, "value" => $eqPar['cno_date_vipusk']['valuestr'], "editable" => false],
                        ["xtype" => "textfield", "name" => "org", "text" => 'Организация обслуживающая оборудование', 'labelWidth' => 180, "value" => $org_name[$userPar['user_org']['valuenum']], "editable" => false],
                    ]
                ]
            ];
        }
        if (fill($v['id']) && $request['status'] != 5) {
            $ans21 = [
                ["xtype" => "fieldset", "text" => 'Описание неисправностей (требуемые работы)',
                    "items" => [
                        ["xtype" => "textarea", "name" => "description", "value" => $comment, "editable" => false],
                    ]
                ],
                ["xtype" => "fieldset", "text" => 'Мероприятие',
                    "items" => [
                        ["xtype" => "textfield", "name" => "operation", "value" => $operData[$opKey], "editable" => false]
                    ]
                ]
            ];
            if ($request['status'] == 4 && $request['sender_id'] == $nowUserId) {
                $ans22 = [
                    ["xtype" => "fieldset", "text" => 'Комментарий исполнителя',
                        "items" => [
                            ["xtype" => "textarea", "name" => "description", 'value' => $request['executor_comment'], "editable" => false],
                        ]
                    ],
                    ["xtype" => "fieldset", "text" => 'Заключение',
                        "items" => [
                            ["xtype" => "textarea", "name" => "conclusion"],
                        ]
                    ]
                ];
            } else {
                if ($request['status'] == 3 && $request['executor_id'] == $nowUserId) {
                    $ans22 = [
                        ["xtype" => "fieldset", "text" => 'Описание работ',
                            "items" => [
                                ["xtype" => "textarea", "name" => "executorComment", "value" => $request['executor_comment']],
                            ]
                        ],
                        ["xtype" => "fieldset", "text" => 'Причина отклонения',
                            "items" => [
                                ["xtype" => "textarea", "name" => "reasonForRejection", "value" => ''],
                            ]
                        ]
                    ];
                } else {
                    $ans22 = [
                        ["xtype" => "fieldset", "text" => 'Описание работ',
                            "items" => [
                                ["xtype" => "textarea", "name" => "executorComment", "value" => $request['executor_comment']],
                            ]
                        ]
                    ];
                }
            }
            $ans2 = array_merge($ans21, $ans22);
        } elseif (fill($v['id']) && $request['status'] == 5) {
            if ($request['sender_id'] == $nowUserId) {                      ////
                $ans2 = [
                    ["xtype" => "fieldset", "text" => 'Описание неисправностей (требуемые работы)',
                        "items" => [
                            ["xtype" => "textarea", "name" => "description", "editable" => false],
                        ]
                    ],
                    ["xtype" => "fieldset", "text" => 'Мероприятие',
                        "items" => [
                            ["xtype" => "textfield", "value" => $operData[$opKey], "name" => "operation", "editable" => false]
                        ]
                    ],
                ];
            } else {
                $ans2 = [
                    ["xtype" => "checkbox", "value" => 0, "boxLabel" => "Срочная", "name" => "urgently", "labelWidth" => 300],
                    ["xtype" => "fieldset", "text" => 'Описание неисправностей (требуемые работы)',
                        "items" => [
                            ["xtype" => "textarea", "name" => "description"],
                        ]
                    ],
                    ["xtype" => "fieldset", "text" => 'Мероприятие',
                        "items" => [
                            ["xtype" => "combo", "value" => fill($operData[$opKey]) ? $operData[$opKey] : 1000, "data" => $operData, "comboData" => $operData, "name" => "operation",]
                        ]
                    ],
                    ["xtype" => "fieldset", "text" => 'Дисциплина',
                        "items" => [
                            ["xtype" => "combo", "value" => $discipline, "data" => $disciplineCombo, "comboData" => $disciplineCombo, "name" => "discipline", "typeAhead" => true, "forceSelection" => true, "editable" => true, "queryMode" => 'local', "displayField" => 'capt',
                                "listeners" => [
                                    "select" => "function(ths,newVal,oldVal){
                                    var panel = Ext.getCmp('".$class_name."');
                                    if (typeof panel != 'undefined') {
                                       ws_panelFunc(this,'loadExecutors',{'discipline':newVal,'groupe_org':'" . $groupeOrg . "'});
                                    }
                                }"
                                ]
                            ]
                        ]
                    ],
                    ["xtype" => "fieldset", "text" => 'Исполнитель',
                        "items" => [
                            ["xtype" => "combo", "value" => /*(fill($executorId) && fill($executorData[$executorId])) ? $executorId : */'', "data" => $executorData, "comboData" => $executorData, "name" => "executor", "typeAhead" => true, "forceSelection" => true, "editable" => true, "queryMode" => 'local', "displayField" => 'capt',]
                        ]
                    ],
                ];
            }
        } else {
            $ans2 = [
                ["xtype" => "checkbox", "value" => 0, "boxLabel" => "Срочная", "name" => "urgently", "labelWidth" => 300],
                ["xtype" => "fieldset", "text" => 'Описание неисправностей (требуемые работы)',
                    "items" => [
                        ["xtype" => "textarea", "name" => "description", 'allowBlank' => false],
                    ]
                ],
                ["xtype" => "fieldset", "text" => 'Мероприятие',
                    "items" => [
                        ["xtype" => "combo", "value" => 1000, "data" => $operData, "comboData" => $operData, "name" => "operation",]
                    ]
                ],
                ["xtype" => "fieldset", "text" => 'Дисциплина',
                    "items" => [
                        ["xtype" => "combo", "value" => $discipline, "data" => $disciplineCombo, "comboData" => $disciplineCombo, "name" => "discipline", "typeAhead" => true, "forceSelection" => true, "editable" => true, "queryMode" => 'local', "displayField" => 'capt',
                            "listeners" => [
                                "select" => "function(ths,newVal,oldVal){
                                    var panel = Ext.getCmp('".$class_name."');
                                    if (typeof panel != 'undefined') {
                                       ws_panelFunc(this,'loadExecutors',{'discipline':newVal,'groupe_org':'" . $groupeOrg . "'});
                                    }
                                }"
                            ]
                        ]
                    ]
                ],
                ["xtype" => "fieldset", "text" => 'Исполнитель',
                    "items" => [
                        ["xtype" => "combo", "value" => /*(fill($executorId) && fill($executorData[$executorId])) ? $executorId : */'', "data" => $executorData, "comboData" => $executorData, "name" => "executor", "typeAhead" => true, "forceSelection" => true, "editable" => true, "queryMode" => 'local', "displayField" => 'capt',]
                    ]
                ],
            ];
        }
        $ans = array_merge($ans1,$ans2);
        return $ans;
    }

    function getDismissFunc($v)
    {
        $responseCode = 'error';
        if (fill($v['executorComment'])) {
            if (fill($v['id'])) {
                $request = $this->model->sel_cno_request_unscheduled(['f' => ['id_request' => $v['id']], 'outtype' => 'single']);
                if (fill($request)) {
                    $senderId = $request['executor_id'];
                    $executorId = $request['sender_id'];
                    $status = 5;
                    if ($request['executor_id'] == $this->loader->core->getCacheData('user_id')) {
                        if ($this->model->upd_cno_request_unscheduled(['f' => ['id_request' => $v['id']], 'status' => $status, 'executor_id' => $executorId, 'sender_id' => $senderId, 'executor_comment' => $v['executorComment']])) {
                            $responseCode = 'ok';
                            $this->model->add_cno_requests_hist([
                                'request_id' => $v['id'],
                                'user_id' => $request['executor_id'],
                                'comment' => $v['executorComment'],
                                'datetime' => date('Ymd',time()),
                                'status' => $status
                            ]);
                        }
                    }
                }
            }
        } else {
            $responseCode = 'noComment';
        }
        return ['responseCode' => $responseCode];
    }

    function getAcceptFunc($v)
    {
        $responseCode = 'error';
        if (fill($v['id'])) {
            $request = $this->model->sel_cno_request_unscheduled(['f' => ['id_request' => $v['id']], 'outtype' => 'single']);
            if (fill($request)) {
                $status = 3;
                if ($request['executor_id'] == $this->loader->core->getCacheData('user_id')) {
                    if ($this->model->upd_cno_request_unscheduled(['f' => ['id_request' => $v['id']], 'status' => $status, 'executor_comment' => $v['executorComment']])) {
                        $responseCode = 'ok';
                        $this->model->add_cno_requests_hist([
                            'request_id' => $v['id'],
                            'user_id' => $request['executor_id'],
                            'comment' => $v['executorComment'],
                            'datetime' => date('Ymd',time()),
                            'status' => $status
                        ]);
                    }
                }
            }
        }
        return ['responseCode' => $responseCode];
    }

    function getCloseRequestFunc($v)
    {
        $responseCode = 'error';
        if (fill($v['id'])) {
            $request = $this->model->sel_cno_request_unscheduled(['f' => ['id_request' => $v['id']], 'outtype' => 'single']);
            if (fill($request)) {
                if ($request['status'] == 4) {
                    if (fill($v['conclusion'])) {
                        if ($this->model->upd_cno_request_unscheduled(['f' => ['id_request' => $v['id']], 'status' => 6, 'due_date' => date('Ymd',time()), 'conclusion' => $v['conclusion']])) {
                            $this->model->add_cno_requests_hist([
                                'request_id' => $v['id'],
                                'user_id' => $request['sender_id'],
                                'comment' => $v['conclusion'],
                                'datetime' => date('Ymd',time()),
                                'status' => 6
                            ]);
                            if (fill($request['ppr_id'])) {
                                if ($this->model->add_cno_ppr_operation([
                                    "equip_id" => $request['equip_id'],
                                    "op_type" => $request['operation_type'],
                                    "ppr_id" => $request['ppr_id'],
                                    'plan_date' => $request['creation_date'],
                                    'fact_date' => date('Ymd',time()),
                                    'comment' => $v['conclusion'],
                                    'user_id' => $request['application_creator'],
                                    'unscheduled' => 1
                                ])) {
                                    if ($this->model->sel_cno_ppr_operation_last(["f" => ['equip_id' => $request['equip_id'], 'op_type' => $request['operation_type']]])) {
                                        $this->model->upd_cno_ppr_operation_last(["f" => ['equip_id' => $request['equip_id'], 'op_type' => $request['operation_type']], "last_date" => date('Ymd',time())]);
                                    } else {
                                        $this->model->add_cno_ppr_operation_last(['equip_id' => $request['equip_id'], 'op_type' => $request['operation_type'], "last_date" => date('Ymd',time())]);
                                    }
                                }
                            }
                            $responseCode = 'ok';
                        }
                    } else {
                        $responseCode = 'noComment';
                    }
                } else {
                    $senderId = $request['executor_id'];
                    $executorId = $request['sender_id'];
                    if ($this->model->upd_cno_request_unscheduled(['f' => ['id_request' => $v['id']], 'status' => 6, 'conclusion' => $v['conclusion'],'executor_id' => $executorId, 'sender_id' => $senderId])) {
                        $responseCode = 'ok';
                        $this->model->add_cno_requests_hist([
                            'request_id' => $v['id'],
                            'user_id' => $request['sender_id'],
                            'comment' => $v['conclusion'],
                            'datetime' => date('Ymd',time()),
                            'status' => 6
                        ]);
                    }
                }
            }
        }
        return ['responseCode' => $responseCode];
    }

    function getExecuteFunc($v)
    {
        $responseCode = 'error';
        if (fill($v['executorComment'])) {
            if (fill($v['id'])) {
                $request = $this->model->sel_cno_request_unscheduled(['f' => ['id_request' => $v['id']], 'outtype' => 'single']);
                if (fill($request)) {
                    $status = 4;
                    if ($request['executor_id'] == $this->loader->core->getCacheData('user_id')) {
                        if ($this->model->upd_cno_request_unscheduled(['f' => ['id_request' => $v['id']], 'status' => $status, 'executor_comment' => $v['executorComment']])) {
                            $responseCode = 'ok';
                            $this->sendMail(['id' => $v['id']]);
                            $this->model->add_cno_requests_hist([
                                'request_id' => $v['id'],
                                'user_id' => $request['executor_id'],
                                'comment' => $v['executorComment'],
                                'datetime' => date('Ymd',time()),
                                'status' => $status
                            ]);
                        }
                    }
                }
            }
        } else {
            $responseCode = 'noComment';
        }
        return ['responseCode' => $responseCode];
    }

    function getResendFunc($v)
    {
        $responseCode = 'error';
        if (fill($v['id'])) {
            $request = $this->model->sel_cno_request_unscheduled(['f' => ['id_request' => $v['id']], 'outtype' => 'single']);
            if (fill($request)) {
                $status = 1;
                if ($v['urgently'] == 'true') {
                    $urg = 1;
                } else {
                    $urg = 0;
                }
                $senderId = $request['executor_id'];
                $executorId = $v['executor'];
                if ($request['executor_id'] == $this->loader->core->getCacheData('user_id')) {
                    if ($this->model->upd_cno_request_unscheduled([
                        'f' => ['id_request' => $v['id']],
                        'status' => $status,
                        'comment' => $v['description'],
                        'operation_type' => $v['operation'],
                        "type_request" => $urg,
                        'executor_id' => $executorId,
                        'sender_id' => $senderId
                    ])) {
                        $responseCode = 'ok';
                        $this->model->add_cno_requests_hist([
                            'request_id' => $v['id'],
                            'user_id' => $request['application_creator'],
                            'comment' => $v['description'],
                            'datetime' => date('Ymd',time()),
                            'status' => $status
                        ]);
                    }
                }
            }
        }
        return ['responseCode' => $responseCode];
    }

    function getCheckPprFunc($v)
    {
        $responseCode = 'error';
        if (fill($v['eq_id'])) {
            $groupe = $this->model->sel_objV(['s' => 'groupe', 'f' => ['obj_id' => $v['eq_id']], 'outtype' => 'single']);
            $operTypeGroupe = $this->smodel->getPprOperType()['groupe'];
            $operType = array_merge($operTypeGroupe['amto'],$operTypeGroupe['amvc']);
            $operTypeEq = array_merge($operTypeGroupe['to'],$operTypeGroupe['rem'],$operTypeGroupe['teh'],$operTypeGroupe['rev'],$operTypeGroupe['quantity']);
            $permittedEvents = ['to1','to2','to3','calibr','verif'];
            foreach ($operType as $operKey => $operVal) {
                if (in_array($operKey,$permittedEvents)) {
                    $operData[$operVal['code']] = $operVal['valuenum'];
                    $operCodeMas[$operVal['valuenum']] = $operVal['code'];
                }
            }
            foreach ($operTypeEq as $operKey => $operVal) {
                $operDataEq[$operVal['code']] = $operVal['valuenum'];
                $operCodeMasEq[$operVal['valuenum']] = $operVal['code'];
            }
            if ((in_array($v['operation'],$operData) && $groupe == 8) || (in_array($v['operation'],$operDataEq) && $groupe == 7)) {
                $pprId = $this->model->sel_cno_ppr_snapshot(['s' => 'ppr_id', 'f' => ['equip_id' => $v['eq_id']]]);
                $pprId = ws_arrayUnique($pprId);
                if (fill($pprId)) {
                    $ppr_array = $this->model->sel_cno_pprv(['s' => 'ppr_id', 'f' => ['ppr_id' => $pprId, 'year' => date('Y',time())], 'out' => 'pattype_code', 'outtype' => 'single']);
                    if (fill($ppr_array)) {
                        $class_name = 'cnoPanel_gridEquipPprPlan';
                        if ($this->loader->checkClass($class_name)) {
                            foreach ($ppr_array as $pattype_code => $item) {
                                $op_type_array = NULL;
                                if (fill($pattype_code)){
                                    $ppr_class = $this->smodel->getPatPprClass(['pattype_code' => $pattype_code])['class'];
                                    $conf = $ppr_class->conf();
                                    $type = $ppr_class->getClassType();
                                    switch ($type) {
                                        case 'ppr':
                                        case 'ppr_zndh':
                                            $op_type_array[$pattype_code] = $conf['toir_types'];
                                            break;
                                        case 'toepb':
                                            $op_type_array[$pattype_code] = $conf['oper'];
                                            break;
                                        case 'toepb_multi':
                                            $op_type_array[$pattype_code] = $conf['oper'];
                                            break;
                                        case 'toepb_multi_toepb':
                                            $op_type_array[$pattype_code] = $conf['oper'];
                                            break;
                                        case 'toepb_trub':
                                            $op_type_array[$pattype_code] = $conf['oper'];
                                            break;
                                        case 'am_to' :
                                            $op_type_array[$pattype_code] = $conf['toir_types'];
                                            break;
                                        case 'am_cv' :
                                            $op_type_array[$pattype_code] = $conf['oper'];
                                            break;
                                        case 'am_att' :
                                            $op_type_array[$pattype_code] = $conf['oper'];
                                            break;
                                        case 'am_att_zndh' :
                                            $op_type_array[$pattype_code] = $conf['oper'];
                                            break;
                                        case 'am_calibr_zndh' :
                                            $op_type_array[$pattype_code] = $conf['oper'];
                                            break;
                                        case 'am_verif_zndh' :
                                            $op_type_array[$pattype_code] = $conf['oper'];
                                            break;
                                        case 'am_to_zndh' :
                                            $op_type_array[$pattype_code] = $conf['toir_types'];
                                            break;
                                        case 'am_verif' :
                                            $op_type_array[$pattype_code] = $conf['oper'];
                                            break;
                                        case 'am_calibr' :
                                            $op_type_array[$pattype_code] = $conf['oper'];
                                            break;
                                    }
                                    if (fill($op_type_array)) {
                                        if ((in_array($operCodeMas[$v['operation']], $op_type_array[$pattype_code]) && $groupe == 8) ||
                                            (in_array($operCodeMasEq[$v['operation']], $op_type_array[$pattype_code]) && $groupe == 7)) {
                                            $v['ppr_id'] = $item;
                                            $responseCode = 'ok';
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                $responseCode = 'ok';
            }
        } elseif ($v['without_eq'] == true)
            $responseCode = 'without_eq_ok';
        return ['responseCode' => $responseCode, 'data' => ['data' => $v]];
    }

    function getRejectFunc($v)
    {
        $responseCode = 'error';
        if (fill($v['id'])) {
            if ($v['reasonForRejection'] != '') {
                $request = $this->model->sel_cno_request_unscheduled(['f' => ['id_request' => $v['id']], 'outtype' => 'single']);
                if (fill($request)) {
                    $senderId = $request['executor_id'];
                    $executorId = $request['sender_id'];
                    $status = 5;
                    if ($request['executor_id'] == $this->loader->core->getCacheData('user_id')) {
                        if ($this->model->upd_cno_request_unscheduled(['f' => ['id_request' => $v['id']], 'status' => $status, 'executor_id' => $executorId, 'sender_id' => $senderId, 'executor_comment' => $v['reasonForRejection']])) {
                            $responseCode = 'ok';
                            $this->sendMail(['id' => $v['id']]);
                            $this->model->add_cno_requests_hist([
                                'request_id' => $v['id'],
                                'user_id' => $request['executor_id'],
                                'comment' => fill($v['executorComment']) ? $v['executorComment'] : $v['reasonForRejection'],
                                'datetime' => date('Ymd',time()),
                                'status' => $status
                            ]);
                        }
                    }
                }
            } else $responseCode = 'noRejection';
        }
        return ['responseCode' => $responseCode];
    }

    function getLoadExecutors($v)
    {
        $responseCode = 'error';
        $data = [];
        if (fill($v['discipline']) && fill($v['groupe_org'])) {
            $groupe_array = $this->model->sel_objV(['f' => ['obj_type_code' => 'cno_groupe'], 'out' => 'obj_id', 's' => 'code', 'outtype' => 'single']);

            foreach ($groupe_array as $id => $code)
                if (!in_array($code,['zndh1', 'zndh2', 'rvp1', 'erintek', 'rvp_all_step']))
                    $groupe_id_array[$id] = $id;

            $groupePars = $this->model->sel_parV(['f' => ['obj_id' => $groupe_id_array], 'out' => ['obj_id','par_type_code'], 's' => 'valuenum', 'outtype' => 'single']);

            foreach ($groupePars as $id => $pars) {
                if ($pars['cno_group_discipline'] == $v['discipline'] && $pars['cno_groupe_org'] == $v['groupe_org'])
                    $groupId[$id] = $id;
            }

            if (fill($groupId)) {
                $executors = $this->model->sel_rel_objV(['f' => ['pobj_id1' => $groupId, 'rel_type_id' => 66], 'out' => 'obj_id', 's' => 'name', 'outtype' => 'single']);
                if (fill($executors)) {
                    foreach ($executors as $exec_id => $exec_name) {
                        $data[] = [
                            'id' => $exec_id,
                            'capt' => $exec_name
                        ];
                    }
                    $responseCode = 'ok';
                } else $responseCode = 'nousers';
            } else $responseCode = 'nousers';
        }
        return ['responseCode' => $responseCode, 'data' => $data];
    }

    function withoutEqSave($v)
    {
        $responseCode = 'error';
        $data = [];
        if (fill($v['executor']) && fill($v['discipline'])) {
            $v['without_eq'] = true;
            $responseCode = $this->loader->reportCnoPanel_main->getSendRequestFunc($v)['responseCode'];
        }
        return ['responseCode' => $responseCode, 'data' => $data];
    }

    function route($routeMod, $v)
    {
        if ($routeMod == "dismiss") $data = $this->getDismissFunc($v);
        if ($routeMod == "accept") $data = $this->getAcceptFunc($v);
        if ($routeMod == "closeRequest") $data = $this->getCloseRequestFunc($v);
        if ($routeMod == "execute") $data = $this->getExecuteFunc($v);
        if ($routeMod == "resend") $data = $this->getResendFunc($v);
        if ($routeMod == "checkPpr") $data = $this->getCheckPprFunc($v);
        if ($routeMod == "reject") $data = $this->getRejectFunc($v);
        if ($routeMod == "loadExecutors") $data = $this->getLoadExecutors($v);
        if ($routeMod == "without_eq_save") $data = $this->withoutEqSave($v);
        return $data;
    }

    function sendMail($v)
    {
        if (fill($v['id'])) {
            $request = $this->model->sel_cno_request_unscheduled(['f' => ['id_request' => $v['id']], 'outtype' => 'single']);
            if (fill($request)) {
                $user_id = $this->loader->core->getCacheData('user_id');
                $names = $this->model->sel_objV(['s' => 'name', 'f' => ['obj_id' => [$user_id,$request['equip_id']]], 'out' => 'obj_id', 'outtype' => 'single']);
                if ($request['status'] == 1) {
                    $bodyText = 'Вы назначены исполнителем.';
                } else if ($request['status'] == 5) {
                    $bodyText = 'Заявка отклонена по причине: ' . $request['executor_comment'];
                } else if ($request['status'] == 4) {
                    $bodyText = 'Заявка требует закрытия.';
                }
                $recipientId = $user_id != $request['sender_id'] ? $request['sender_id'] : $request['executor_id'];
                $recipientAddres = $this->model->sel_parV(['s' => 'valuestr', 'f' => ['obj_id' => $recipientId, 'par_type_code' => 'user_email'], 'outtype' => 'single']);
                $from = 'sys_cno@nestro.ru';
                $from_name = '<АО \'Зарубежнефть\'. Система мониторинга целостности нефтегазопромыслового оборудования>';
                $link = 'Ссылка на систему: http://15-vs-asodu.nestro.ru';
                $subject =  'Сообщение по заявке объекта "' . $names[$request['equip_id']] . '" отправлено Вам от: ' . $names[$user_id];
                $body = 'Заявка сформирована: '.date('d.m.Y', strtotime($request['creation_date'])).';'.PHP_EOL. $bodyText.';'.PHP_EOL. $link;
                $mail = new PHPMailer();
                $mail->IsSMTP();
                $mail->SMTPDebug = 2;
                $mail->Debugoutput = 'error_log';
                $mail->SMTPAutoTLS = false;
                $mail->Host = 'owa.nestro.ru';
                $mail->Port = 25;
                $mail->CharSet = 'UTF-8';
                $mail->SetFrom($from, $from_name);
                $mail->Subject = $subject;
                $mail->Body = $body;
                $mail->AddAddress($recipientAddres);
//                $mail->AddAddress('abiktashev@intas-company.com');
                $mail->AddBCC('anovruzov@intas-company.com');
//                $mail->AddBCC('iovsyannikov@intas-company.com');
                $mail->Send();
            }
        }
    }

    function getWsFunc($v)
    {
        $report = $this->model->sel_obj(['f'=>['code'=>'report_cno_application_creation'],'outtype'=>'single']);

        $m["report"] = "function(ths,panel,val){
            val=val || {}; 
            var date=ws_collectContainerValue(panel,{return:'object'});
            var value = {
                report_id: '" . $report['obj_id'] . "',
                eq_id: '" . $v['obj_id'] . "',
                location: date.location,
                fio: date.fio,
                name_eq: date.name_eq,
                serial_number: date.serial_number,
                issue_year: date.issue_year,
                org: date.org,
                description: date.description,
                urgently: date.urgently,
                operation: date.operation,
                executor: date.executor,
                discipline: date.discipline,
                without_eq: '" . $v['without_eq'] . "',
                type: '" . $v['type'] . "'
            };
            val.maskPanel=panel;
            var func = {
                ok: function(val,processData){
                    ws_panel_unmask(val.maskPanel);
                    value.ppr_id = processData.data.ppr_id;
                    ws_panelFunc(panel,'close');
                    ws_panelEmbed({'embedType':'tab'},['md', 'reportCno', 'panel', 'main', 'get',ws_udd(value)],{},{});   
                },
                without_eq_ok: function(val,processData){
                    ws_panelFunc(panel,'without_eq_save',value); 
                },
                error:function (val) {
                    ws_panel_unmask(val.maskPanel);
                    " . $this->loader->ext->getNotify(["text" => "Для заведения заявки, добавьте оборудование в рабочий график ТОиР текущего года", "type" => "warning"]) . ";
                },
                processing: function(val,processData){
                    var store= val.maskPanel.getStore();
                    store.loadData(processData,false);
                },
                callback:function (val) {
                }
            };
            ws_request(['md','" . $this->getModuleCode() . "','panel','" . $this->getPanelCode() . "','checkPpr',ws_udd(value)],val,func);
        }";


        $m["without_eq_save"] = "function(ths,panel,val){
             val=val || {};
             var func = {
                error:function (val) {" . $this->loader->ext->getNotify(["text" => "Ошибка", "type" => "error"]) . ";},
                ok: function (val, processData) {
                    ws_panelEmbed({embedType:'tab'},['md','cno','panel','wrapRequests','get',ws_udd()]);
                    ws_panelFunc(Ext.getCmp('cno_gridSendRequests'),'update',value);
                    ws_panelFunc(panel,'close');
                },
                callback: function (val, processData){ 
                    ws_panel_unmask(val.maskPanel);
                }
             };
             val.panel=panel;
             ws_panel_mask(panel);
             var value=ws_collectContainerValue(panel,{return:'object'});
             var ws_data=ws_getWSData(panel);
             if (typeof ws_data=='object')  value=$.extend({},value, ws_data);
             ws_request(['md','" . $this->getModuleCode() . "','panel','" . $this->getPanelCode() . "','without_eq_save', ws_udd(value)],val,func);
         }";

        $m["toAccept"] = "function(ths,panel,val){
            val=val || {}; 
            val.maskPanel=panel;
            var func = {
                ok: function(val){
                    " . $this->loader->ext->getNotify(["text" => "Заявка принята", "type" => "success"]) . ";
                    ws_panelFunc(Ext.getCmp('cno_gridSendRequests'),'update');
                    ws_panelFunc(Ext.getCmp('cno_gridRecRequests'),'update');
                    ws_panelFunc(panel,'close');
                },
                error:function (val) {
                    " . $this->loader->ext->getNotify(["text" => "Неверные данные", "type" => "warning"]) . ";
                },
                processing: function(val,processData){
                    var store= val.maskPanel.getStore();
                    store.loadData(processData,false);
                },
                callback:function (val) {
                    ws_panel_unmask(val.maskPanel);
                }
            };
            var data=ws_collectContainerValue(panel,{return:'object'});
            var ws_data=ws_getWSData(panel);
            var value;
            value = {
                id : val.id,
                executorComment : data.executorComment
            };
            ws_request(['md','" . $this->getModuleCode() . "','panel','" . $this->getPanelCode() . "','accept',ws_udd(value)],val,func);
        }";

        $m["toDismiss"] = "function(ths,panel,val){
            val=val || {}; 
            val.maskPanel=panel;
            var func = {
                ok: function(val){
                    " . $this->loader->ext->getNotify(["text" => "Заявка отклонена", "type" => "success"]) . ";
                    ws_panelFunc(Ext.getCmp('cno_gridSendRequests'),'update');
                    ws_panelFunc(Ext.getCmp('cno_gridRecRequests'),'update');
                    ws_panelFunc(panel,'close');
                },
                noComment:function (val) {
                    " . $this->loader->ext->getNotify(["text" => "Заполните поле &laquo;Описание работ&raquo;", "type" => "warning"]) . ";
                },
                error:function (val) {
                    " . $this->loader->ext->getNotify(["text" => "Неверные данные", "type" => "warning"]) . ";
                },
                processing: function(val,processData){
                    var store= val.maskPanel.getStore();
                    store.loadData(processData,false);
                },
                callback:function (val) {
                    ws_panel_unmask(val.maskPanel);
                }
            };
            var data=ws_collectContainerValue(panel,{return:'object'});
            var ws_data=ws_getWSData(panel);
            var value;
            value = {
                executorComment: data.executorComment,
                id:val.id
            };
            ws_request(['md','" . $this->getModuleCode() . "','panel','" . $this->getPanelCode() . "','dismiss',ws_udd(value)],val,func);   
        }";

        $m["reject"] = "function(ths,panel,val){
            val=val || {}; 
                val.maskPanel=panel;
                var func = {
                ok: function(val){
                    " . $this->loader->ext->getNotify(["text" => "Заявка отклонена", "type" => "success"]) . ";
                        ws_panelFunc(Ext.getCmp('cno_gridSendRequests'),'update');
                        ws_panelFunc(Ext.getCmp('cno_gridRecRequests'),'update');
                        ws_panelFunc(panel,'close');
                },
                noRejection:function (val) {
                    " . $this->loader->ext->getNotify(["text" => "Заполните поле &laquo;Причина отклонения&raquo;", "type" => "warning"]) . ";
                },
                error:function (val) {
                    " . $this->loader->ext->getNotify(["text" => "Неверные данные", "type" => "warning"]) . ";
                },
                processing: function(val,processData){
                    var store= val.maskPanel.getStore();
                    store.loadData(processData,false);
                },
                callback:function (val) {
                    ws_panel_unmask(val.maskPanel);
                }
            };
            var data=ws_collectContainerValue(panel,{return:'object'});
            var ws_data=ws_getWSData(panel);
            var value;
            value = {
                reasonForRejection: data.reasonForRejection,
                id:val.id
            };
            ws_request(['md','" . $this->getModuleCode() . "','panel','" . $this->getPanelCode() . "','reject',ws_udd(value)],val,func);   
        }";

        $m["loadExecutors"] = "function(ths,panel,val){
             val=val || {};
             var func = {
                error:function (val) {" . $this->loader->ext->getNotify(["text" => "Ошибка", "type" => "error"]) . ";},
                nousers:function (val) {
                    " . $this->loader->ext->getNotify(["text" => "Нет доступного списка пользователей", "type" => "error"]) . ";
                },
                ok: function (val, processData) {
                    
                },
                callback: function (val, processData){ 
                    ws_panel_unmask(val['panel']);
                    var comboStore = ws_panelGetFieldsByName(panel,'executor').getStore();
                    ws_panelGetFieldsByName(panel,'executor').setValue(null);
                    comboStore.loadData(processData);
                }
             };
             val.panel=panel;
             ws_panel_mask(panel);
             var value=ws_collectContainerValue(panel,{return:'object'});
             value.groupe_org = val.groupe_org;
             var ws_data=ws_getWSData(panel);
             if (typeof ws_data=='object')  value=$.extend({},value, ws_data);
             ws_request(['md','" . $this->getModuleCode() . "','panel','" . $this->getPanelCode() . "','loadExecutors', ws_udd(value)],val,func);
         }";

        $m["closeRequest"] = "function(ths,panel,val){
            val=val || {}; 
            val.maskPanel=panel;
            var func = {
                ok: function(val){
                    " . $this->loader->ext->getNotify(["text" => "Заявка закрыта", "type" => "success"]) . ";
                    ws_panelFunc(Ext.getCmp('cno_gridSendRequests'),'update');
                    ws_panelFunc(Ext.getCmp('cno_gridRecRequests'),'update');
                    ws_panelFunc(panel,'close');
                },
                noComment:function (val) {
                    " . $this->loader->ext->getNotify(["text" => "Заполните поле &laquo;Заключение&raquo;", "type" => "warning"]) . ";
                },
                error:function (val) {
                    " . $this->loader->ext->getNotify(["text" => "Неверные данные", "type" => "warning"]) . ";
                },
                processing: function(val,processData){
                    var store= val.maskPanel.getStore();
                    store.loadData(processData,false);
                },
                callback:function (val) {
                    ws_panel_unmask(val.maskPanel);
                }
            };
            var data=ws_collectContainerValue(panel,{return:'object'});
            var ws_data=ws_getWSData(panel);
            var value;
            value = {
                id: val.id,
                conclusion: data.conclusion
            };
            ws_request(['md','" . $this->getModuleCode() . "','panel','" . $this->getPanelCode() . "','closeRequest',ws_udd(value)],val,func);
        }";

        $m["execute"] = "function(ths,panel,val){
            val=val || {}; 
            val.maskPanel=panel;
            var func = {
                ok: function(val){
                    " . $this->loader->ext->getNotify(["text" => "Отчет о выполнении отправлен", "type" => "success"]) . ";
                    ws_panelFunc(Ext.getCmp('cno_gridSendRequests'),'update');
                    ws_panelFunc(Ext.getCmp('cno_gridRecRequests'),'update');
                    ws_panelFunc(panel,'close');
                },
                noComment:function (val) {
                    " . $this->loader->ext->getNotify(["text" => "Заполните поле &laquo;Описание работ&raquo;", "type" => "warning"]) . ";
                },
                error:function (val) {
                    " . $this->loader->ext->getNotify(["text" => "Неверные данные", "type" => "warning"]) . ";
                },
                processing: function(val,processData){
                    var store= val.maskPanel.getStore();
                    store.loadData(processData,false);
                },
                callback:function (val) {
                    ws_panel_unmask(val.maskPanel);
                }
            };
            var data=ws_collectContainerValue(panel,{return:'object'});
            var ws_data=ws_getWSData(panel);
            var value;
            value = val;
            value = {
                executorComment: data.executorComment,
                id:val.id
            };
            ws_request(['md','" . $this->getModuleCode() . "','panel','" . $this->getPanelCode() . "','execute',ws_udd(value)],val,func);
        }";

        $m["closePanel"] = "function(ths,panel,val){
            val=val || {}; 
            ws_panelFunc(panel,'close');
        }";

        $m['resend'] = "function(ths,panel,val){
            val=val || {}; 
            val.maskPanel=panel;
            var func = {
                processing: function(val,processData){
                    ws_panelFunc(Ext.getCmp('cno_gridSendRequests'),'update');
                    ws_panelFunc(Ext.getCmp('cno_gridRecRequests'),'update');
                    ws_panelFunc(panel,'close');
                },
                callback:function (val) {
                    ws_panel_unmask(val.maskPanel);
                }
            };
            var date=ws_collectContainerValue(panel,{return:'object'});
            var ws_data=ws_getWSData(panel);
            var value;
            value = val;
            var value = {
                urgently: date.urgently,
                description: date.description,
                operation: date.operation,
                id: val.id,
                executor: date.executor,
            };
            ws_request(['md','" . $this->getModuleCode() . "','panel','" . $this->getPanelCode() . "','resend',ws_udd(value)],val,func);
        }";

        return $m;
    }
}