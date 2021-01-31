<?php

class ws_cnoPanel_startDashboardPanel extends ws_abstractView
{
    private  $dataArray = [];
    private  $groupId = null;
    private  $userId = null;
    public $moduleCode = null;
    public $panelCode = null;

    function __construct($v = [])
    {
        $this->mapClass($v);
        $classNameM = explode("_", ws_getClassNameByObj($this));
        $this->moduleCode = str_replace("Panel", '', $classNameM[0]);
        $this->panelCode = $classNameM[1];
        $this->loader = $v['loader'];
        $this->model = $this->loader->model;
        $this->dmodel = $this->loader->dataModel;
        $this->amodel = $this->loader->advModel;
        $smodel = $this->moduleCode . 'Model';
        if ($this->loader->checkClass($smodel)) $this->smodel = $this->loader->$smodel;
        $master = $this->moduleCode;
        if ($this->loader->checkClass($master)) $this->master = $this->loader->$master;
        $userId = $this->loader->core->getCacheData("user_id");
        $groupId = $this->model->sel_rel_objv(['s' => 'pobj_id1', 'f' => ['obj_id' => $userId, 'rel_type_id' => 66], 'outtype' => 'single']);
        $this->groupId = $groupId;
        $this->userId = $userId;
        $panelCode = $this->getModuleCode() . "_" . $this->getPanelCode() . "_" . $groupId . '_' . date('Y-m-d');
        $casheData = $this->model->sel_cno_panel_cache(['s' => 'data', 'out' => 'loaded_time', 'outtype' => 'single', 'f' => ["panel_id" => $panelCode]]);
        $this->dataArray['flag_update'] = true;
        if (fill($casheData)) {
            foreach ($casheData as $key => $value) {
                $keyCache = $key;
                $valueCache = $value;
            }
            $cacheTime = strtotime(date('H:i:s'), time()) - strtotime($keyCache);
            $this->dataArray = array_merge($this->dataArray, json_decode($valueCache, true));
            if (fill($valueCache) && $cacheTime < 3600) {
                $this->dataArray['flag_update'] = false;
            }
        }
        if (!fill($casheData) || !fill($valueCache)) {
            $this->dataArray = $this->getUpdateCache();
        }
    }

    function getModuleCode()
    {
        return $this->moduleCode;
    }

    function getPanelCode()
    {
        return $this->panelCode;
    }

    function getEventConfig($v)
    {
        $m['init'] = ["func" => "init", "update" => true];
        return $m;
    }

    function getUpdateCache($v) {
        $equip_access = $this->model->sel_cno_armmeh_equip(['s' => 'equip_id_obj_access_without_status', 'f' => ['group_id' => $this->groupId], 'outtype' => 'single']);
        if(fill($equip_access)) {
            $equip_id_array = explode(",", $equip_access);
            $objIdM = ws_arrayUnique($equip_id_array);
        }
        $org = $this->smodel->getCnoOrgAccess()['org'];
        $_t = [];
        if (fill($objIdM) && $_t = $this->model->sel_parv(['s' => 'obj_id', 'f' => ['valuenum' => $org, 'obj_id' => $objIdM, 'par_type_code' => 'cno_ceh']])) ;
        if (fill($_t)) $objIdM = $_t;
        $data['obj_idM'] = $objIdM;
        $data['process_panel'] = ws_arrayMerge($data['process_panel'], $this->getAccidents($v));
        $data['process_panel'] = ws_arrayMerge($data['process_panel'], $this->getSafety($v));
        $data['process_panel'] = ws_arrayMerge($data['process_panel'], $this->getAge($v));
        $data['process_panel'] = ws_arrayMerge($data['process_panel'], $this->getAgeComp($v));
        $data['process_panel'] = ws_arrayMerge($data['process_panel'], $this->getToir($v));
        $systemFill = $this->getSystemFill($v);
        $data['process_panel']['fill_meh_1'] = (fill($systemFill['fill_meh_1'])) ? $systemFill['fill_meh_1'] : 100;
        $data['ws_func']['risk'] = $this->getRisk($v);
        $data['ws_func']['stateEquipMeh'] = $this->getStateEquipMeh($v);
        $data['ws_func']['systemFill'] = $systemFill;
        $data['ws_func']['safetyDyn'] = $this->getSafetyDyn($v);
        $data['ws_func']['reportEquipStateStat'] = $this->model->sel_obj(['s' => 'obj_id', 'f' => ['code' => 'report_cno_equip_state_stat'], 'outtype' => 'single']);
        $data['ws_func']['reportEquipStateDyn'] = $this->model->sel_obj(['s' => 'obj_id', 'f' => ['code' => 'report_cno_equip_state_dyn'], 'outtype' => 'single']);
        $data['ws_func']['reportToirYear'] = $this->model->sel_obj(['s' => 'obj_id', 'f' => ['code' => 'report_cno_toir_year'], 'outtype' => 'single']);
        $data['ws_func']['reportToirMonth'] = $this->model->sel_obj(['s' => 'obj_id', 'f' => ['code' => 'report_cno_toir_month'], 'outtype' => 'single']);
        $data['ws_func']['reportAccidentsYear'] = $this->model->sel_obj(['s' => 'obj_id', 'f' => ['code' => 'report_cno_accidents_year'], 'outtype' => 'single']);
        $data['ws_func']['reportAccidentsMonth'] = $this->model->sel_obj(['s' => 'obj_id', 'f' => ['code' => 'report_cno_accidents_month'], 'outtype' => 'single']);
        $data['ws_func']['reportAgeComp'] = $this->model->sel_obj(['s' => 'obj_id', 'f' => ['code' => 'report_cno_equip_age_comp'], 'outtype' => 'single']);
        $data['ws_func']['reportFillMeh2'] = $this->model->sel_obj(['s' => 'obj_id', 'f' => ['code' => 'report_cno_fill_meh2'], 'outtype' => 'single']);
        $data['ws_func']['reportFillMeh3'] = $this->model->sel_obj(['s' => 'obj_id', 'f' => ['code' => 'report_cno_fill_meh3'], 'outtype' => 'single']);
        $data['ws_func']['reportFillMeh4'] = $this->model->sel_obj(['s' => 'obj_id', 'f' => ['code' => 'report_cno_fill_meh4'], 'outtype' => 'single']);
        $userId = $this->userId;
        $data['zn_obj_id'] = $this->model->sel_objv(['s' => 'obj_id', 'f' => ['code' => 'org_zarubezh'], 'outtype' => 'single']);
        $data['user'] = $this->model->sel_obj(['s' => 'name', 'f' => ['obj_id' => $userId], 'outtype' => 'single']);
        $orgArray = ['94391' => 'rvp', '108718' => 'znds', '116349' => 'zndh'];
        foreach ($data['process_panel'] as $d => $ditem) {
            foreach ($orgArray as $oa => $oaitem) {
                if (mb_strpos($d, $oa)) {
                    $data['process_panel'][str_replace($oa, $oaitem, $d)] = $ditem;
                    unset($data['process_panel'][$d]);
                }
            }
        }
        foreach ($data['ws_func'] as $d => $ditem) {
            foreach ($orgArray as $oa => $oaitem) {
                if (mb_strpos($d, $oa)) {
                    $data['process_panel'][str_replace($oa, $oaitem, $d)] = $ditem;
                    unset($data['process_panel'][$d]);
                }
            }
        }
        $data['date'] = date('d.m.Y');
        if ($v['datetime'] == $data['date'] || !fill($v['datetime'])) {
            $jsonData = ws_jsonEncode($data);
            $panelCode = $this->getModuleCode() . "_" . $this->getPanelCode() . "_" . $this->groupId . '_' . date('Y-m-d');
            if ($this->model->sel_cno_panel_cache(['s' => 'panel_id', 'outtype' => 'single', 'f' => ["panel_id" => $panelCode]])) {
                $this->model->upd_cno_panel_cache(['f' => ["panel_id" => $panelCode], "loaded_time" => date("H:i:s"), 'data' => $jsonData]);
            } else {
                if ($this->model->add_cno_panel_cache(["panel_id" => $panelCode, "loaded_time" => date("H:i:s"), 'data' => $jsonData])) {
                    $data['flag_update'] = false;
                }

            }
        } elseif (fill($v['datetime'])) {
            $data['date'] = $v['datetime'];
            $jsonData = ws_jsonEncode($data);
            $panelCode = $this->getModuleCode() . "_" . $this->getPanelCode() . "_" . $this->groupId . '_' . date('Y-m-d', strtotime($v['datetime']));
            $this->model->add_cno_panel_cache(["panel_id" => $panelCode, "loaded_time" => date("H:i:s"), 'data' => $jsonData]);
        }
        $data['date'] = fill($v['datetime']) ? $v['datetime'] : date('d.m.Y');
        return $data;
    }

    function route($routeMod, $v)
    {
        if ($routeMod == "update")
            $data = $this->getUpdateFunc($v);
        if ($routeMod == "riskMatr")
            $data = $this->checkRiskMatr($v);
        if ($routeMod == "equipStateStat")
            $data = $this->checkEquipStateStat($v);
        if ($routeMod == "equipStateDyn")
            $data = $this->checkEquipStateDyn($v);
        if ($routeMod == "toirYear")
            $data = $this->checkToirYear($v);
        if ($routeMod == "toirMonth")
            $data = $this->checkToirMonth($v);
        if ($routeMod == "ageComp")
            $data = $this->checkAgeComp($v);
        if ($routeMod == "fillMeh2")
            $data = $this->checkFillMeh2($v);
        if ($routeMod == "fillMeh3")
            $data = $this->checkFillMeh3($v);
        if ($routeMod == "fillMeh4")
            $data = $this->checkFillMeh4($v);
        return $data;
    }

    function getUpdateFunc($v)
    {
        if(fill($v['datetime'])) {
            $panelCode = $this->getModuleCode() . "_" . $this->getPanelCode() . "_" . $this->groupId . '_' . date('Y-m-d', strtotime($v['datetime']));
            $casheData = $this->model->sel_cno_panel_cache(['s' => 'data', 'out' => 'loaded_time', 'outtype' => 'single', 'f' => ["panel_id" => $panelCode]]);
            if (fill($casheData)) {
                foreach ($casheData as $key => $value) {
                    $keyCache = $key;
                    $valueCache = $value;
                }
                $this->dataArray = array_merge($this->dataArray, json_decode($valueCache, true));
                if ($keyCache == date()) {
                    $cacheTime = strtotime($keyCache);
                    if (fill($valueCache) && $cacheTime < 3600) {
                        $newData = $this->getUpdateCache($v);
                    }
                }
            }
            if (fill($valueCache)) $newData = json_decode($valueCache, true);
            if (!fill($casheData) || !fill($valueCache)) {
                $newData = $this->getUpdateCache($v);
            }
        } else {
            $newData = $this->getUpdateCache($v);
        }
        $data = $newData['process_panel'];
        $data['datetime'] = $newData['date'];
        $dataGauge = $newData['ws_func'];
        $date = $newData['date'];
        $zn_obj_id = $newData['zn_obj_id'];

        $data['riskMatrOnClick'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'riskMatr',{'code':'org_zarubezh','obj_id':'" . $zn_obj_id . "','date':'" . $date . "'});";
        $data['riskMatrCursor'] = "pointer";
        $data['accidentsMonthOnClick'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'accidentsMonth',{'do':'all','code':'org_zarubezh','obj_id':'" . $zn_obj_id . "','date':'" . $date . "'});";
        $data['accidentsMonthCursor'] = "pointer";
        $data['accidentsYearOnClick'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'accidentsYear',{'do':'all','code':'org_zarubezh','obj_id':'" . $zn_obj_id . "','date':'" . $date . "'});";
        $data['accidentsYearCursor'] = "pointer";
        $data['accidentsMonthOnClick_rvp'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'accidentsMonth',{'do':'rvp','code':'org_zarubezh','obj_id':'" . $zn_obj_id . "','date':'" . $date . "'});";
        $data['accidentsMonthOnClick_znds'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'accidentsMonth',{'do':'znds','code':'org_zarubezh','obj_id':'" . $zn_obj_id . "','date':'" . $date . "'});";
        $data['accidentsMonthOnClick_zndh'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'accidentsMonth',{'do':'zndh','code':'org_zarubezh','obj_id':'" . $zn_obj_id . "','date':'" . $date . "'});";
        $data['accidentsYearOnClick_rvp'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'accidentsYear',{'do':'rvp','code':'org_zarubezh','obj_id':'" . $zn_obj_id . "','date':'" . $date . "'});";
        $data['accidentsYearOnClick_znds'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'accidentsYear',{'do':'znds','code':'org_zarubezh','obj_id':'" . $zn_obj_id . "','date':'" . $date . "'});";
        $data['accidentsYearOnClick_zndh'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'accidentsYear',{'do':'zndh','code':'org_zarubezh','obj_id':'" . $zn_obj_id . "','date':'" . $date . "'});";

        $data['stateEquipDynOnClick'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'equipStateDyn',{'obj_id':'" . $zn_obj_id . "','date':'" . $date . "'});";
        $data['stateEquipDynCursor'] = "pointer";
        $data['stateEquipStatOnClick'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'equipStateStat',{'obj_id':'" . $zn_obj_id . "','date':'" . $date . "'});";
        $data['stateEquipStatCursor'] = "pointer";

        $data['toirYearOnClick'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'toirYear',{'obj_id':'" . $zn_obj_id . "','date':'" . $date . "'});";
        $data['toirYearCursor'] = "pointer";
        $data['toirMonthOnClick'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'toirMonth',{'obj_id':'" . $zn_obj_id . "','date':'" . $date . "'});";
        $data['toirMonthCursor'] = "pointer";

        $data['ageCompOnClick'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'ageComp',{'type':0,'obj_id':'" . $zn_obj_id . "','date':'" . $date . "'});";
        $data['ageCompCursor'] = "pointer";
        $data['ageComp1OnClick'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'ageComp',{'type':1,'obj_id':'" . $zn_obj_id . "','date':'" . $date . "'});";
        $data['ageComp1Cursor'] = "pointer";
        $data['ageComp2OnClick'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'ageComp',{'type':2,'obj_id':'" . $zn_obj_id . "','date':'" . $date . "'});";
        $data['ageComp2Cursor'] = "pointer";
        $data['ageComp3OnClick'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'ageComp',{'type':3,'obj_id':'" . $zn_obj_id . "','date':'" . $date . "'});";
        $data['ageComp3Cursor'] = "pointer";
        $data['ageComp4OnClick'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'ageComp',{'type':4,'obj_id':'" . $zn_obj_id . "','date':'" . $date . "'});";
        $data['ageComp4Cursor'] = "pointer";
        $data['ageComp5OnClick'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'ageComp',{'type':5,'obj_id':'" . $zn_obj_id . "','date':'" . $date . "'});";
        $data['ageComp5Cursor'] = "pointer";
        $data['ageComp6OnClick'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'ageComp',{'type':6,'obj_id':'" . $zn_obj_id . "','date':'" . $date . "'});";
        $data['ageComp6Cursor'] = "pointer";
        $data['ageComp7OnClick'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'ageComp',{'type':7,'obj_id':'" . $zn_obj_id . "','date':'" . $date . "'});";
        $data['ageComp7Cursor'] = "pointer";

        $data['fillMeh2OnClick'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'fillMeh2',{'obj_id':'" . $zn_obj_id . "','date':'" . $date . "'});";
        $data['fillMeh2Cursor'] = "pointer";
        $data['fillMeh3OnClick'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'fillMeh3',{'obj_id':'" . $zn_obj_id . "','date':'" . $date . "'});";
        $data['fillMeh3Cursor'] = "pointer";
        $data['fillMeh4OnClick'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'fillMeh4',{'obj_id':'" . $zn_obj_id . "','date':'" . $date . "'});";
        $data['fillMeh4Cursor'] = "pointer";
        return ['data' => ['data' => [$data], 'dataGauge' => $dataGauge]];
    }

    function getStateEquip($v)
    {
        $stateMeh = [["В работе", 50], ["В резерве", 30], ["В ремонте", 10], ["Выведено из эксплуатации", 10]];
        $stateEnerg = [["В работе", 50], ["В резерве", 30], ["В ремонте", 10], ["Выведено из эксплуатации", 10]];
        $stateMetr = [["В работе", 50], ["В резерве", 30], ["В ремонте", 10], ["Выведено из эксплуатации", 10]];
        $datetime = fill($v['datetime']) ? strtotime($v['datetime']) : time();
        $stateMeh = ws_jsonEncode($stateMeh);
        $stateEnerg = ws_jsonEncode($stateEnerg);
        $stateMetr = ws_jsonEncode($stateMetr);
        $statesDef = ['stateMeh' => $stateMeh, 'stateEnerg' => $stateEnerg, 'stateMetr' => $stateMetr];
        $states = $this->model->sel_cno_dashboard_state(['s' => 'state', 'f' => ["datetime" => date("Ymd", $datetime), 'type' => 'all'], 'out' => 'direction', 'outtype' => 'single']);
        if (!fill($states)) {
            $states = $statesDef;
        }
        $colors = ['#92CF50', '#4CB4FF', '#F87263', '#FDAF44'];
        if (fill($states)) {
            $stateEquipJson = $states;
            foreach ($stateEquipJson as $sej => $sejitem) {
                $stateEquip[$sej] = json_decode($sejitem, true);
                $count[$sej] = 0;
                $states[$sej . '_max'] = 0;
                $states[$sej . '_point'] = '';
                foreach ($stateEquip[$sej] as $se => $seitem) {
                    if ($states[$sej . '_max'] <= $seitem[1]) {
                        $states[$sej . '_max'] = $seitem[1];
                        $states[$sej . '_point'] .= "<span style=\"color:" . $colors[$se] . "\">●</span>";
                        $count[$sej]++;
                    }
                }
                if ($count[$sej] >= 3) {
                    $states[$sej . '_point'] .= '<br>';
                }
            }
        }
        return $states;
    }

    function getStateEquipMeh($v)
    {
        $stateDyn = [["В работе", 50], ["В резерве", 30], ["В ремонте", 10], ["Выведено из эксплуатации", 10]];
        $stateStat = [["В работе", 50], ["В резерве", 30], ["В ремонте", 10], ["Выведено из эксплуатации", 10]];
        $datetime = fill($v['datetime']) ? strtotime($v['datetime']) : time();
        $stateDyn = ws_jsonEncode($stateDyn);
        $stateStat = ws_jsonEncode($stateStat);
        $statesDef = ['stateDyn' => $stateDyn, 'stateStat' => $stateStat];
        $states = $this->model->sel_cno_dashboard_state(['s' => 'state', 'f' => ["datetime" => date("Ymd", $datetime), 'type' => ['stateDyn', 'stateStat']], 'out' => 'type', 'outtype' => 'single']);
        if (!fill($states)) {
            $states = $statesDef;
        }
        $colors = ['#92CF50', '#4CB4FF', '#F87263', '#FDAF44'];
        if (fill($states)) {
            $stateEquipJson = $states;
            foreach ($stateEquipJson as $sej => $sejitem) {
                $stateEquip[$sej] = json_decode($sejitem, true);
                $count[$sej] = 0;
                $states[$sej . '_max'] = 0;
                $states[$sej . '_point'] = '';
                foreach ($stateEquip[$sej] as $se => $seitem) {
                    if ($states[$sej . '_max'] <= $seitem[1]) {
                        $states[$sej . '_max'] = $seitem[1];
                        $states[$sej . '_point'] .= "<span style=\"color:" . $colors[$se] . "\">●</span>";
                        $count[$sej]++;
                    }
                }
                if ($count[$sej] >= 3) {
                    $states[$sej . '_point'] .= '<br>';
                }
            }
        }
        return $states;
    }

    function getRisk($v)
    {
        $riskMeh = 0;
        $riskEnerg = 0;
        $riskMetr = 0;
        $datetime = fill($v['datetime']) ? strtotime($v['datetime']) : time();
        $riskDef = ['riskMeh' => $riskMeh, 'riskEnerg' => $riskEnerg, 'riskMetr' => $riskMetr];
        $risk = $this->model->sel_cno_dashboard_risk(['s' => 'value', 'f' => ["datetime" => date("Ymd", $datetime)], 'out' => 'direction', 'outtype' => 'single']);
        if (!fill($risk)) {
            $risk = $riskDef;
        }
        foreach ($risk as $r => $ritem) {
            $risk[$r . '_reverse'] = 100 - $ritem;
        }
        return $risk;
    }

    function getAccidents($v)
    {
        $datetime = fill($v['datetime']) ? strtotime($v['datetime']) : time();
        $fromYear = strtotime(date("Y-01-01", $datetime));
        $fromMonth = strtotime(date("Y-m-01", $datetime));
        $accidents = ['accidentsYear_94391' => 0, 'accidentsYear_108718' => 0, 'accidentsYear_116349' => 0,
            'accidentsYearHeight_94391' => 15, 'accidentsYearHeight_108718' => 15, 'accidentsYearHeight_116349' => 15,
            'accidentsYearColor_94391' => 'green', 'accidentsYearColor_108718' => 'green', 'accidentsYearColor_116349' => 'green',
            'accidentsMonth_94391' => 0, 'accidentsMonth_108718' => 0, 'accidentsMonth_116349' => 0,
            'accidentsMonthHeight_94391' => 15, 'accidentsMonthHeight_108718' => 15, 'accidentsMonthHeight_116349' => 15,
            'accidentsMonthColor_94391' => 'green', 'accidentsMonthColor_108718' => 'green', 'accidentsMonthColor_116349' => 'green'
        ];
        $accidentsYear = $this->model->sel_cno_dashboard_accidents(['f' => [
            'timeend' => ['field' => "datetime", 'val' => date("Ymd H:i:s", $datetime), "operate" => "<="],
            'timestart' => ['field' => "datetime", 'val' => date("Ymd H:i:s", $fromYear), "operate" => ">="]], 'out' => 'obj_id']);
        $accidentsMonth = $this->model->sel_cno_dashboard_accidents(['f' => [
            'timeend' => ['field' => "datetime", 'val' => date("Ymd H:i:s", $datetime), "operate" => "<="],
            'timestart' => ['field' => "datetime", 'val' => date("Ymd H:i:s", $fromMonth), "operate" => ">="]], 'out' => 'obj_id']);
        $yearMax = 0;
        $monthMax = 0;
        foreach ($accidentsYear as $ay => $ayitem) {
            if ($yearMax < count($ayitem)) {
                $yearMax = count($ayitem);
            }
            $accidents['accidentsYear_' . $ay] = count($ayitem);
        }
        foreach ($accidentsMonth as $am => $amitem) {
            if ($monthMax < count($amitem)) {
                $monthMax = count($amitem);
            }
            $accidents['accidentsMonth_' . $am] = count($amitem);
        }
        foreach ($accidentsYear as $ay => $ayitem) {
            $accidents['accidentsYearHeight_' . $ay] = round((count($ayitem) / $yearMax) * 85);
            if ($accidents['accidentsYearHeight_' . $ay] <= 45) {
                $accidents['accidentsYearColor_' . $ay] = 'green';
            } elseif ($accidents['accidentsYearHeight_' . $ay] <= 85) {
                $accidents['accidentsYearColor_' . $ay] = 'yellow';
            } else {
                $accidents['accidentsYearColor_' . $ay] = 'red';
            }
        }
        foreach ($accidentsMonth as $am => $amitem) {
            $accidents['accidentsMonthHeight_' . $am] = round((count($amitem) / $monthMax) * 85);
            if ($accidents['accidentsMonthHeight_' . $am] <= 45) {
                $accidents['accidentsMonthColor_' . $am] = 'green';
            } elseif ($accidents['accidentsMonthHeight_' . $am] <= 85) {
                $accidents['accidentsMonthColor_' . $am] = 'yellow';
            } else {
                $accidents['accidentsMonthColor_' . $am] = 'red';
            }
        }
        return $accidents;
    }

    function getAccidentsDyn($v)
    {
        $accidents = [];
        $datetime = fill($v['datetime']) ? strtotime($v['datetime']) : time();
        $datestart = strtotime(date("Y-01", $datetime));
        for ($tmp = (int)(date("Ym", $datestart)); $tmp <= (int)((date("Y", $datetime)) . '12'); $tmp++) {
            $accidentsTmp[$tmp] = 0;
        }
        $accidentsYear = $this->model->sel_cno_dashboard_accidents(['f' => [
            'timeend' => ['field' => "datetime", 'val' => date("Ymd H:i:s", $datetime), "operate" => "<="],
            'timestart' => ['field' => "datetime", 'val' => date("Ymd H:i:s", $datestart), "operate" => ">="]], 'out' => 'datetime']);
        foreach ($accidentsYear as $ay => $ayitem) {
            $accidentsTmp[date("Ym", strtotime($ay))] = fill($accidents[date("Ym", strtotime($ay))]) ? $accidents[date("Ym", strtotime($ay))] + count($ayitem) : count($ayitem);
        }
        foreach ($accidentsTmp as $at => $atitem) {
            $accidents[] = $atitem;
        }
        $accidents = ws_jsonEncode($accidents);
        return $accidents;
    }

    function getToir($v)
    {
        $toirMeh = 100;
        $toirMehCount = 1000;
        $toirMehCountTotal = 1000;
        $datetime = fill($v['datetime']) ? strtotime($v['datetime']) : time();
        $toirDef = ['toirMeh_year' => $toirMeh, 'toirMeh_year_undone' => (100 - $toirMeh), 'toirMeh_yearCount' => $toirMehCount, 'toirMeh_yearCountTotal' => $toirMehCountTotal, 'toirMeh_yearCount_undone' => $toirMehCountTotal - $toirMehCount,
            'toirMeh_month' => $toirMeh, 'toirMeh_month_undone' => (100 - $toirMeh), 'toirMeh_monthCount' => $toirMehCount, 'toirMeh_monthCountTotal' => $toirMehCountTotal, 'toirMeh_monthCount_undone' => $toirMehCountTotal - $toirMehCount];
        $toir = $toirDef;
        $toirTMP = $this->model->sel_cno_dashboard_toir(['f' => ["datetime" => date("Ymd", $datetime)], 'out' => ['direction', 'period', 'op_type'], 'outtype' => 'single']);
        $undoneToir = $this->model->sel_cno_dashboard_toir_undone_DO(['f' => ['direction' => 'toirMeh', 'datetime' => date('d.m.Y', $datetime)], 's' => 'op_id']);
        $undoneToir = ws_arrayUnique($undoneToir);
        $undoneYear = 0;
        $undoneMonth = 0;
        if (fill($undoneToir)) {
            $opM = $this->model->sel_cno_ppr_operation(['f' => ['op_id' => $undoneToir,
                'timestart' => ['field' => "plan_date", 'val' => date("Y-m-d H:i:s", $datetime), "operate" => "<="],
                'timeend' => ['field' => "plan_date", 'val' => date("Y-m-d H:i:s", strtotime('01.01.' . date('Y', $datetime))), "operate" => ">="]],
                'out' => 'op_id', 'outtype' => 'single', 's' => 'plan_date']);
            foreach ($opM as $keyOper => $valueOper) {
                $undoneYear++;
                if (strtotime($valueOper) >= strtotime('01.' . date('m.Y', $datetime)) && strtotime($valueOper) <= $datetime)
                    $undoneMonth++;
            }
        }
        foreach ($toirTMP as $t0 => $titem0) {
            foreach ($titem0 as $t => $titem) {
                $toir[$t0 . '_' . $t . 'Count'] = 0;
                $toir[$t0 . '_' . $t . 'CountTotal'] = 0;
                $toir[$t0 . '_' . $t . 'Count_undone'] = 0;
                foreach ($titem as $t1 => $titem1) {
                    if (fill($titem1['total_count'])) {
                        $toir[$t0 . '_' . $t . 'Count'] += $titem1['done_count'];
                        $toir[$t0 . '_' . $t . 'CountTotal'] += $titem1['total_count'];
                        $toir[$t0 . '_' . $t . 'Count_undone'] += $titem1['total_count'] - $titem1['done_count'];
                    }
                }
                $toir[$t0 . '_' . $t . 'Count_undone'] = ($t == 'year') ? $undoneYear : $undoneMonth;
                $toir[$t0 . '_' . $t] = round($toir[$t0 . '_' . $t . 'Count'] * 100 / $toir[$t0 . '_' . $t . 'CountTotal'], 0);
                $toir[$t0 . '_' . $t . '_undone'] = 100 - $toir[$t0 . '_' . $t];
            }
        }
        return $toir;
    }

    function getSafety($v)
    {
        $safetyRvp = 100;
        $safetyZnds = 100;
        $safetyZndh = 100;
        $datetime = fill($v['datetime']) ? strtotime($v['datetime']) : time();
        $safetyDef = ['safetyCurr_94391' => $safetyRvp, 'safetyCurrTend_94391' => 'up', 'safetyCurr_color_94391' => 'green',
            'safetyCurr_108718' => $safetyZnds, 'safetyCurrTend_108718' => 'up', 'safetyCurr_color_108718' => 'green',
            'safetyCurr_116349' => $safetyZndh, 'safetyCurrTend_116349' => 'up', 'safetyCurr_color_116349' => 'green'];
        $safetyBeforeM = $this->model->sel_cno_dashboard_safety(['f' => ["datetime" => date("Ymd", $datetime - 86400)], 'out' => ['obj_id', 'direction'], 'outtype' => 'single']);
        foreach ($safetyBeforeM as $sba => $sbaitem) {
            $safetyBeforeTmp = 0;
            $safetyBeforeCount = 0;
            $safetyBeforeMehTmp = 0;
            $safetyBeforeMehCount = 0;
            foreach ($sbaitem as $sba1 => $sbaitem1) {
                if (fill(mb_strpos($sba1, 'meh'))) {
                    $safetyBeforeMehTmp += $sbaitem1['value'];
                    $safetyBeforeMehCount++;
                }
            }
            if ($safetyBeforeMehCount > 0) {
                $safetyBeforeTmp += round($safetyBeforeMehTmp / $safetyBeforeMehCount);
                $safetyBeforeCount++;
            }
            $safetyBefore[$sba] = ($safetyBeforeCount > 0) ? round($safetyBeforeTmp / $safetyBeforeCount) : 0;
        }
        $safetyM = $this->model->sel_cno_dashboard_safety(['f' => ["datetime" => date("Ymd", $datetime)], 'out' => ['obj_id', 'direction'], 'outtype' => 'single']);
        foreach ($safetyM as $sa => $saitem) {
            $safetyTmp = 0;
            $safetyCount = 0;
            $safetyMehTmp = 0;
            $safetyMehCount = 0;
            foreach ($saitem as $sa1 => $saitem1) {
                if (fill(mb_strpos($sa1, 'meh'))) {
                    $safetyMehTmp += $saitem1['value'];
                    $safetyMehCount++;
                }
            }
            if ($safetyMehCount > 0) {
                $safetyTmp += round($safetyMehTmp / $safetyMehCount);
                $safetyCount++;
            }
            if ($safetyCount > 0) {
                $safety['safetyCurr_' . $sa] = round($safetyTmp / $safetyCount);
            }

            $safety['safetyCurrTend_' . $sa] = ($safety['safetyCurr_' . $sa] >= $safetyBefore[$sa]) ? 'up' : 'down';
            if ($safety['safetyCurr_' . $sa] >= 75) {
                $safety['safetyCurr_color_' . $sa] = 'green';
            } elseif ($safety['safetyCurr_' . $sa] >= 45) {
                $safety['safetyCurr_color_' . $sa] = 'yellow';
            } else {
                $safety['safetyCurr_color_' . $sa] = 'red';
            }
        }
        if (!fill($safety)) {
            $safety = $safetyDef;
        }

        return $safety;
    }

    function getAge($v)
    {
        $age = [];
        $ageRvp = 0;
        $ageZnds = 0;
        $ageZndh = 0;
        $datetime = fill($v['datetime']) ? strtotime($v['datetime']) : time();
        $ageDef = ['ageCurr_94391' => $ageRvp, 'ageCurr_width_94391' => 10, 'ageCurr_color_94391' => 'green',
            'ageCurr_108718' => $ageZnds, 'ageCurr_width_108718' => 10, 'ageCurr_color_108718' => 'yellow',
            'ageCurr_116349' => $ageZndh, 'ageCurr_width_116349' => 10, 'ageCurr_color_116349' => 'green'];
        $ageM = $this->model->sel_cno_dashboard_age(['s' => 'value', 'f' => ["datetime" => date("Ymd", $datetime)], 'out' => ['obj_id'], 'outtype' => 'single']);
        foreach ($ageM as $sa => $saitem) {
            $age['ageCurr_' . $sa] = $saitem;
            $age['ageCurr_width_' . $sa] = ($saitem > 100) ? 100 : $saitem;
            if ($age['ageCurr_' . $sa] <= 45) {
                $age['ageCurr_color_' . $sa] = 'green';
            } elseif ($age['ageCurr_' . $sa] <= 85) {
                $age['ageCurr_color_' . $sa] = 'yellow';
            } else {
                $age['ageCurr_color_' . $sa] = 'red';
            }
        }
        if (!fill($age)) {
            $age = $ageDef;
        }
        return $age;
    }

    function getAgeComp($v)
    {
        $ageComp = [];
        $ageComp_meh1 = 14;
        $ageComp_meh2 = 14;
        $ageComp_meh3 = 14;
        $ageComp_meh4 = 14;
        $ageComp_meh5 = 14;
        $ageComp_meh6 = 14;
        $ageComp_meh7 = 14;
        $datetime = fill($v['datetime']) ? strtotime($v['datetime']) : time();
        $ageCompDef = ['ageComp_meh_1' => $ageComp_meh1, 'ageComp_meh_1_width' => 50, 'ageComp_meh_2' => $ageComp_meh2, 'ageComp_meh_2_width' => 50,
            'ageComp_meh_3' => $ageComp_meh3, 'ageComp_meh_3_width' => 50, 'ageComp_meh_4' => $ageComp_meh4, 'ageComp_meh_4_width' => 50,
            'ageComp_meh_5' => $ageComp_meh5, 'ageComp_meh_5_width' => 50, 'ageComp_meh_6' => $ageComp_meh6, 'ageComp_meh_6_width' => 50,
            'ageComp_meh_7' => $ageComp_meh7, 'ageComp_meh_7_width' => 50];
        $ageCompM = $this->model->sel_cno_dashboard_age_comp(['s' => 'value', 'f' => ["datetime" => date("Ymd", $datetime)], 'out' => ['type'], 'outtype' => 'single']);
        foreach ($ageCompM as $sa => $saitem) {
            $ageCompJson['ageComp_' . $sa] = json_decode($saitem, true);
            foreach ($ageCompJson as $acj => $acjitem) {
                $max[$acj] = 0;
                foreach ($acjitem as $acj1 => $acjitem1) {
                    $ageComp[$acjitem1[0]] = $acjitem1[1];
                    if ($max[$acj] < $acjitem1[1]) {
                        $max[$acj] = $acjitem1[1];
                    }
                }
                foreach ($acjitem as $acj2 => $acjitem2) {
                    $widthTmp = round(($acjitem2[1] / $max[$acj]) * 85);
                    $ageComp[$acjitem2[0] . '_width'] = ($widthTmp < 15) ? 15 : $widthTmp;
                }
            }
        }
        if (!fill($ageComp)) $ageComp = $ageCompDef;
        return $ageComp;
    }

    function getSystemFill($v)
    {
        $datetime = fill($v['datetime']) ? strtotime($v['datetime']) : time();
        $fillDef = ['fill_meh_1' => 100, 'fill_meh_2' => 100, 'fill_meh_3' => 100, 'fill_meh_4' => 100];
        $fill = $fillDef;
        $systemFill = $this->model->sel_cno_dashboard_fill(['s' => 'value', 'f' => ["datetime" => date("Ymd", $datetime), 'direction' => 'meh'], 'out' => 'type', 'outtype' => 'single']);
        foreach ($systemFill as $sf => $sfitem) {
            $fill['fill_meh_' . $sf] = $sfitem;
        }
        return $fill;
    }

    function getSafetyDyn($v)
    {
        $safetyAll = [];
        $countAll = [];
        $safetyDynAll = [];
        $safetyMehTmp = [];
        $cat = [1 => 'Янв', 2 => 'Фев', 3 => 'Мар', 4 => 'Апр', 5 => 'Май', 6 => 'Июн', 7 => 'Июл', 8 => 'Авг', 9 => 'Сен', 10 => 'Окт', 11 => 'Ноя', 12 => 'Дек'];

        $datetime = fill($v['datetime']) ? strtotime($v['datetime']) : time();
        $datestart = strtotime(date("Ym01", strtotime("-1 year", time())));
        $safetyM = $this->model->sel_cno_dashboard_safety(['f' => [
            'timeend' => ['field' => "datetime", 'val' => date("Ymd H:i:s", $datetime), "operate" => "<="],
            'timestart' => ['field' => "datetime", 'val' => date("Ymd H:i:s", $datestart), "operate" => ">="]], 'out' => ['obj_id', 'datetime'], 'outtype' => 'single']);
        foreach ($safetyM as $sa => $saitem) {
            foreach ($saitem as $sa2 => $saitem2) {
                if (date("j", strtotime($sa2)) >= 15) {
                    $date = date("Ym", strtotime($sa2)) . '_15';
                } else {
                    $date = date("Ym", strtotime($sa2));
                }
                $categor[$date] = $cat[(int)date("m", strtotime($sa2))];
                $safetyMehTmp[$sa][$date] = $saitem2['value'];
            }
        }
        foreach ($categor as $catVal) {
            $categories[] = $catVal;
        }
        //смотрим все записи по направлению, и кладем среднеарифметическое на последний день месяца(или 14 число месяца) в массив
        foreach ($safetyMehTmp as $smt => $smtitem) {
            foreach ($smtitem as $smt1 => $smtitem1) {
                $safetyDynAll['safetyDyn_' . $smt . '_' . $smt1] = $smtitem1;
            }
        }
        foreach ($safetyDynAll as $sda => $sdaitem) {
            $safetyAll[$sda] = $sdaitem;
        }
        //Обработка данных по ДО, суммирование значений по ДО в ЗН
        $orgArray = ['94391' => 'rvp', '108718' => 'znds', '116349' => 'zndh'];
        foreach ($safetyAll as $dsa => $dsaitem) {
            foreach ($orgArray as $oa => $oaitem) {
                if (mb_strpos($dsa, $oa)) {
                    $safetyAll['safety_' . $oaitem][$dsa] = $dsaitem;
                    unset($safetyAll[$dsa]);
                    $countAll['safety_zn'][str_replace($oa . '_', '', $dsa)] = fill($countAll['safety_zn'][str_replace($oa . '_', '', $dsa)]) ? $countAll['safety_zn'][str_replace($oa . '_', '', $dsa)] + 1 : 1;
                    $safetyAll['safety_zn'][str_replace($oa . '_', '', $dsa)] = fill($safetyAll['safety_zn'][str_replace($oa . '_', '', $dsa)]) ? $safetyAll['safety_zn'][str_replace($oa . '_', '', $dsa)] + $dsaitem : $dsaitem;
                }
            }
        }
        //Последняя обработка данных, досчитывание среднего по ЗН значения
        foreach ($safetyAll['safety_zn'] as $dsa => $dsaitem) {
            if ($countAll['safety_zn'][$dsa] > 0 && $dsaitem > 0) {
                $safetyAll['safety_zn'][$dsa] = round($dsaitem / $countAll['safety_zn'][$dsa]);
            }
        }
        //Упаковка данных в json
        foreach ($safetyAll as $i1 => $item1) {
            foreach ($item1 as $i11 => $item11) {
                unset($safetyAll[$i1][$i11]);
                if ($i11 == 'safetyDyn_94391_201806') {

                } else if ($i11 == 'safetyDyn_201806') {

                } else if ($i11 == 'safetyDyn_108718_201806') {

                } else if ($i11 == 'safetyDyn_116349_201806') {

                } else $safetyAll[$i1][] = $item11;
            }
            $safetyAll[$i1] = ws_jsonEncode($safetyAll[$i1]);
        }
        return ['categories' => $categories, 'safetyDynAll' => $safetyAll];
    }

    function checkRiskMatr($v)
    {
        $responseCode = 'error';
        $date = fill($v['date']) ? $v['date'] : date('d.m.Y');
        $obj_id = $this->smodel->getCnoObjAccess()['pobj_id1'];
        if (fill($v['obj_id'])) {
            if ($this->model->sel_cno_dashboard_risk_matr(['f' => ['obj_id' => $obj_id, 'direction' => 'riskMeh', 'datetime' => $date], 'top' => 1])) {
                $responseCode = 'ok';
            }
        }
        return ["responseCode" => $responseCode];
    }

    function checkEquipStateStat($v)
    {
        $responseCode = 'error';
        $date = fill($v['date']) ? $v['date'] : date('d.m.Y');
        if (fill($v['obj_id'])) {
            if ($this->model->sel_cno_dashboard_state_meh(['f' => ['direction' => 'stateMeh', 'datetime' => $date, 'type' => 1], 'top' => 1])) {
                $responseCode = 'ok';
            }
        }
        return ["responseCode" => $responseCode];
    }

    function checkEquipStateDyn($v)
    {
        $responseCode = 'error';
        $date = fill($v['date']) ? $v['date'] : date('d.m.Y');
        if (fill($v['obj_id'])) {
            if ($this->model->sel_cno_dashboard_state_meh(['f' => ['direction' => 'stateMeh', 'datetime' => $date, 'type' => 2], 'top' => 1])) {
                $responseCode = 'ok';
            }
        }
        return ["responseCode" => $responseCode];
    }

    function checkToirYear($v)
    {
        $responseCode = 'error';
        $date = fill($v['date']) ? $v['date'] : date('d.m.Y');
        if (fill($v['obj_id'])) {
            if ($this->model->sel_cno_dashboard_toir_DO(['f' => ['direction' => 'toirMeh', 'datetime' => $date, 'period' => 'year'], 'top' => 1])) {
                $responseCode = 'ok';
            }
        }
        return ["responseCode" => $responseCode];
    }

    function checkToirMonth($v)
    {
        $responseCode = 'error';
        $date = fill($v['date']) ? $v['date'] : date('d.m.Y');
        if (fill($v['obj_id'])) {
            if ($this->model->sel_cno_dashboard_toir_DO(['f' => ['direction' => 'toirMeh', 'datetime' => $date, 'period' => 'month'], 'top' => 1])) {
                $responseCode = 'ok';
            }
        }
        return ["responseCode" => $responseCode];
    }

    function checkAgeComp($v)
    {
        $responseCode = 'error';
        $date = fill($v['date']) ? $v['date'] : date('d.m.Y');
        if (fill($v['obj_id'])) {
            if ($this->model->sel_cno_dashboard_age_DO(['f' => ['datetime' => $date], 'top' => 1])) {
                $responseCode = 'ok';
            }
        }
        return ["responseCode" => $responseCode];
    }

    function checkFillMeh2($v)
    {
        $responseCode = 'error';
        $date = fill($v['date']) ? $v['date'] : date('d.m.Y');
        if (fill($v['obj_id'])) {
            if ($this->model->sel_cno_dashboard_fill_DO(['f' => ['direction' => 'meh', 'datetime' => $date, 'type' => 2], 'top' => 1])) {
                $responseCode = 'ok';
            }
        }
        return ["responseCode" => $responseCode];
    }

    function checkFillMeh3($v)
    {
        $responseCode = 'error';
        $date = fill($v['date']) ? $v['date'] : date('d.m.Y');
        if (fill($v['obj_id'])) {
            if ($this->model->sel_cno_dashboard_fill_DO(['f' => ['direction' => 'meh', 'datetime' => $date, 'type' => 3], 'top' => 1])) {
                $responseCode = 'ok';
            }
        }
        return ["responseCode" => $responseCode];
    }

    function checkFillMeh4($v)
    {
        $responseCode = 'error';
        $date = fill($v['date']) ? $v['date'] : date('d.m.Y');
        if (fill($v['obj_id'])) {
            if ($this->model->sel_cno_dashboard_fill_DO(['f' => ['direction' => 'meh', 'datetime' => $date, 'type' => 4], 'top' => 1])) {
                $responseCode = 'ok';
            }
        }
        return ["responseCode" => $responseCode];
    }

    function processPanel()
    {
        $userId = $this->loader->core->getCacheData('user_id');
        $userName = $this->model->sel_objv(['f'=>['obj_id' => $userId], 's' => 'name', 'outtype'=>'single']);
        $dataForProcessPanel = $this->dataArray;
        $data = $dataForProcessPanel['process_panel'];
        $data['riskMatrOnClick'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'riskMatr',{'code':'org_zarubezh','obj_id':'" . $dataForProcessPanel['zn_obj_id'] . "','date':'" . $dataForProcessPanel['date'] . "'});";
        $data['riskMatrCursor'] = "pointer";
        $data['accidentsMonthOnClick'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'accidentsMonth',{'do':'all','code':'org_zarubezh','obj_id':'" . $dataForProcessPanel['zn_obj_id'] . "','date':'" . $dataForProcessPanel['date'] . "'});";
        $data['accidentsMonthCursor'] = "pointer";
        $data['accidentsYearOnClick'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'accidentsYear',{'do':'all','code':'org_zarubezh','obj_id':'" . $dataForProcessPanel['zn_obj_id'] . "','date':'" . $dataForProcessPanel['date'] . "'});";
        $data['accidentsYearCursor'] = "pointer";
        $data['accidentsMonthOnClick_rvp'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'accidentsMonth',{'do':'rvp','code':'org_zarubezh','obj_id':'" . $dataForProcessPanel['zn_obj_id'] . "','date':'" . $dataForProcessPanel['date'] . "'});";
        $data['accidentsMonthOnClick_znds'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'accidentsMonth',{'do':'znds','code':'org_zarubezh','obj_id':'" . $dataForProcessPanel['zn_obj_id'] . "','date':'" . $dataForProcessPanel['date'] . "'});";
        $data['accidentsMonthOnClick_zndh'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'accidentsMonth',{'do':'zndh','code':'org_zarubezh','obj_id':'" . $dataForProcessPanel['zn_obj_id'] . "','date':'" . $dataForProcessPanel['date'] . "'});";
        $data['accidentsYearOnClick_rvp'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'accidentsYear',{'do':'rvp','code':'org_zarubezh','obj_id':'" . $dataForProcessPanel['zn_obj_id'] . "','date':'" . $dataForProcessPanel['date'] . "'});";
        $data['accidentsYearOnClick_znds'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'accidentsYear',{'do':'znds','code':'org_zarubezh','obj_id':'" . $dataForProcessPanel['zn_obj_id'] . "','date':'" . $dataForProcessPanel['date'] . "'});";
        $data['accidentsYearOnClick_zndh'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'accidentsYear',{'do':'zndh','code':'org_zarubezh','obj_id':'" . $dataForProcessPanel['zn_obj_id'] . "','date':'" . $dataForProcessPanel['date'] . "'});";

        $data['stateEquipDynOnClick'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'equipStateDyn',{'obj_id':'" . $dataForProcessPanel['zn_obj_id'] . "','date':'" . $dataForProcessPanel['date'] . "'});";
        $data['stateEquipDynCursor'] = "pointer";
        $data['stateEquipStatOnClick'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'equipStateStat',{'obj_id':'" . $dataForProcessPanel['zn_obj_id'] . "','date':'" . $dataForProcessPanel['date'] . "'});";
        $data['stateEquipStatCursor'] = "pointer";

        $data['toirYearOnClick'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'toirYear',{'obj_id':'" . $dataForProcessPanel['zn_obj_id'] . "','date':'" . $dataForProcessPanel['date'] . "'});";
        $data['toirYearCursor'] = "pointer";
        $data['toirMonthOnClick'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'toirMonth',{'obj_id':'" . $dataForProcessPanel['zn_obj_id'] . "','date':'" . $dataForProcessPanel['date'] . "'});";
        $data['toirMonthCursor'] = "pointer";

        $data['ageCompOnClick'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'ageComp',{'type':0,'obj_id':'" . $dataForProcessPanel['zn_obj_id'] . "','date':'" . $dataForProcessPanel['date'] . "'});";
        $data['ageCompCursor'] = "pointer";
        $data['ageComp1OnClick'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'ageComp',{'type':1,'obj_id':'" . $dataForProcessPanel['zn_obj_id'] . "','date':'" . $dataForProcessPanel['date'] . "'});";
        $data['ageComp1Cursor'] = "pointer";
        $data['ageComp2OnClick'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'ageComp',{'type':2,'obj_id':'" . $dataForProcessPanel['zn_obj_id'] . "','date':'" . $dataForProcessPanel['date'] . "'});";
        $data['ageComp2Cursor'] = "pointer";
        $data['ageComp3OnClick'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'ageComp',{'type':3,'obj_id':'" . $dataForProcessPanel['zn_obj_id'] . "','date':'" . $dataForProcessPanel['date'] . "'});";
        $data['ageComp3Cursor'] = "pointer";
        $data['ageComp4OnClick'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'ageComp',{'type':4,'obj_id':'" . $dataForProcessPanel['zn_obj_id'] . "','date':'" . $dataForProcessPanel['date'] . "'});";
        $data['ageComp4Cursor'] = "pointer";
        $data['ageComp5OnClick'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'ageComp',{'type':5,'obj_id':'" . $dataForProcessPanel['zn_obj_id'] . "','date':'" . $dataForProcessPanel['date'] . "'});";
        $data['ageComp5Cursor'] = "pointer";
        $data['ageComp6OnClick'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'ageComp',{'type':6,'obj_id':'" . $dataForProcessPanel['zn_obj_id'] . "','date':'" . $dataForProcessPanel['date'] . "'});";
        $data['ageComp6Cursor'] = "pointer";
        $data['ageComp7OnClick'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'ageComp',{'type':7,'obj_id':'" . $dataForProcessPanel['zn_obj_id'] . "','date':'" . $dataForProcessPanel['date'] . "'});";
        $data['ageComp7Cursor'] = "pointer";

        $data['fillMeh2OnClick'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'fillMeh2',{'obj_id':'" . $dataForProcessPanel['zn_obj_id'] . "','date':'" . $dataForProcessPanel['date'] . "'});";
        $data['fillMeh2Cursor'] = "pointer";
        $data['fillMeh3OnClick'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'fillMeh3',{'obj_id':'" . $dataForProcessPanel['zn_obj_id'] . "','date':'" . $dataForProcessPanel['date'] . "'});";
        $data['fillMeh3Cursor'] = "pointer";
        $data['fillMeh4OnClick'] = "onClick=ws_panelFunc(Ext.getCmp('cno_startDashboardPanel'),'fillMeh4',{'obj_id':'" . $dataForProcessPanel['zn_obj_id'] . "','date':'" . $dataForProcessPanel['date'] . "'});";
        $data['fillMeh4Cursor'] = "pointer";
        $m = [
            "layout" => "border",
            "items" =>
                [
                    "xtype" => 'dataview',
                    "fullscreen" => true,
                    "name" => "cnoDashboardZn",
                    "region" => 'center',
                    "autoScroll" => true,
                    "style" => ["textAlign" => 'center', "backgroundColor" => '#ffffff'],
                    "itemSelector" => "div.x-dataview-item",
                    "store" => [
                        "autoLoad" => false,
                        "sortOnLoad" => true,
                        "fields" => [
                            'accidentsYear_rvp', 'accidentsYear_znds', 'accidentsYear_zndh',
                            'accidentsMonth_rvp', 'accidentsMonth_znds', 'accidentsMonth_zndh',
                            'accidentsYearHeight_rvp', 'accidentsYearHeight_znds', 'accidentsYearHeight_zndh',
                            'accidentsMonthHeight_rvp', 'accidentsMonthHeight_znds', 'accidentsMonthHeight_zndh',
                            'accidentsYearColor_rvp', 'accidentsYearColor_znds', 'accidentsYearColor_zndh',
                            'accidentsMonthColor_rvp', 'accidentsMonthColor_znds', 'accidentsMonthColor_zndh',
                            'toirMeh_year', 'toirMeh_year_undone', 'toirMeh_yearCount', 'toirMeh_yearCountTotal', 'toirMeh_yearCount_undone',
                            'toirMeh_month', 'toirMeh_month_undone', 'toirMeh_monthCount', 'toirMeh_monthCountTotal', 'toirMeh_monthCount_undone',
                            'safetyCurr_rvp', 'safetyCurr_znds', 'safetyCurr_zndh',
                            'safetyCurrTend_rvp', 'safetyCurrTend_znds', 'safetyCurrTend_zndh',
                            'safetyCurr_color_rvp', 'safetyCurr_color_znds', 'safetyCurr_color_zndh',
                            'ageCurr_rvp', 'ageCurr_znds', 'ageCurr_zndh',
                            'ageCurr_width_rvp', 'ageCurr_width_znds', 'ageCurr_width_zndh',
                            'ageCurr_color_rvp', 'ageCurr_color_znds', 'ageCurr_color_zndh',
                            'ageComp_meh_1', 'ageComp_meh_1_width',
                            'ageComp_meh_2', 'ageComp_meh_2_width',
                            'ageComp_meh_3', 'ageComp_meh_3_width',
                            'ageComp_meh_4', 'ageComp_meh_4_width',
                            'ageComp_meh_5', 'ageComp_meh_5_width',
                            'ageComp_meh_6', 'ageComp_meh_6_width',
                            'ageComp_meh_7', 'ageComp_meh_7_width',
                            'riskMatrOnClick', 'riskMatrCursor',
                            'accidentsMonthOnClick', 'accidentsMonthCursor',
                            'accidentsYearOnClick', 'accidentsYearCursor',
                            'accidentsMonthOnClick_rvp', 'accidentsMonthOnClick_znds', 'accidentsMonthOnClick_zndh',
                            'accidentsYearOnClick_rvp', 'accidentsYearOnClick_znds', 'accidentsYearOnClick_zndh',
                            'stateEquipDynOnClick', 'stateEquipDynCursor',
                            'stateEquipStatOnClick', 'stateEquipStatCursor',
                            'toirYearOnClick', 'toirYearCursor',
                            'toirMonthOnClick', 'toirMonthCursor',
                            'ageCompOnClick', 'ageCompCursor',
                            'ageComp1OnClick', 'ageComp1Cursor',
                            'ageComp2OnClick', 'ageComp2Cursor',
                            'ageComp3OnClick', 'ageComp3Cursor',
                            'ageComp4OnClick', 'ageComp4Cursor',
                            'ageComp5OnClick', 'ageComp5Cursor',
                            'ageComp6OnClick', 'ageComp6Cursor',
                            'ageComp7OnClick', 'ageComp7Cursor',
                            'fillMeh2OnClick', 'fillMeh2Cursor',
                            'fillMeh3OnClick', 'fillMeh3Cursor',
                            'fillMeh4OnClick', 'fillMeh4Cursor',
                        ],
                        "data" => $data
                    ],
                    "tpl" => [
                        "<tpl for='.'>", "
                            <div class='cno-dashboard'>
                                <header class='header'>
                                    <div class='header__item'>
                                        <a href='cnoDashboard.php' class='header-logo'>АИС ЦНО</a>
                                        <ul class='header-nav'>
                                            <li class='header-nav__item header-nav__item--active'>
                                                <a href='cnoDashboard.php'>ЗН</a>
                                            </li>
                                            <li class='header-nav__item'>
                                                <a href='cnoDashboard_RVP.php'>РВП</a>
                                            </li>
                                            <li class='header-nav__item'>
                                                <a href='cnoDashboard_ZNDH.php'>ЗНДХ</a>
                                            </li>
                                            <li class='header-nav__item'>
                                                <a href='cnoDashboard_ZNDS.php'>ЗНДС</a>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class='header__item header-nav__item--user'>
                                        <div class='header-user'>
                                        </div>
                                        <div class='header-user-data'>
                                            <span>" . $userName . "</span>
                                        </div>
                                    </div>
                                </header>
                                <main class='main'>
                                    <div class='main-flex'>
                                        <div class='main-sidebar'>

                                            <div class='main-sidebar-datepicker-wrap' style='display: block;'>
                                                <div id='dashboardDatepicker' class='sidebar-calendar'></div>
                                            </div>

                                            <div id='main-sidebar-data-all'>
                                                <table class='safety-rating'>
                                                    <caption>Рейтинг надежности</caption>
                                                    <tbody>
                                                        <tr>
                                                            <td></td>
                                                            <td class='safety-rating__title safety-rating__title--{safetyCurrTend_rvp}'>РВП</td>
                                                            <td class='safety-rating__value safety-rating__val--{safetyCurrTend_rvp}'>
                                                                <span class='safety-rating__value--{safetyCurr_color_rvp}'>{safetyCurr_rvp}%</span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td></td>
                                                            <td class='safety-rating__title safety-rating__title--{safetyCurrTend_znds}'>ЗНДС</td>
                                                            <td class='safety-rating__value safety-rating__val--{safetyCurrTend_znds}'>
                                                                <span class='safety-rating__value--{safetyCurr_color_znds}'>{safetyCurr_znds}%</span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td></td>
                                                            <td class='safety-rating__title  safety-rating__title--{safetyCurrTend_zndh}'>ЗНДХ</td>
                                                            <td class='safety-rating__value safety-rating__val--{safetyCurrTend_zndh}'>
                                                                <span class='safety-rating__value--{safetyCurr_color_zndh}'>{safetyCurr_zndh}%</span>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                                <div class='sidebar-scale' id='incedentScaleResponsive'>
                                                    <div class='sidebar-scale-header'>
                                                        <span class='sidebar-scale-header__text'>Количество инцидентов</span>
                                                    </div>

                                                    <div class='sidebar-scale-body {accidentsMonthCursor}' {accidentsMonthOnClick} id='sidebarScaleDataMonth' style='display: none;'>
                                                        <div class='sidebar-scale-body__item' {accidentsMonthOnClick_rvp} data-scale-item-count='{accidentsMonth_rvp}' data-scale-item-title='РВП'>
                                                            <div class='sidebar-scale-level'>
                                                                <span class='sidebar-scale-level--{accidentsMonthColor_rvp}' style='height: {accidentsMonthHeight_rvp}%;' data-responsive-value='{accidentsMonthHeight_rvp}%'></span>
                                                            </div>
                                                        </div>
                                                        <div class='sidebar-scale-body__item' {accidentsMonthOnClick_znds} data-scale-item-count='{accidentsMonth_znds}' data-scale-item-title='ЗНДС'>
                                                            <div class='sidebar-scale-level'>
                                                                <span class='sidebar-scale-level--{accidentsMonthColor_znds}' style='height: {accidentsMonthHeight_znds}%;' data-responsive-value='{accidentsMonthHeight_znds}%'></span>
                                                            </div>
                                                        </div>
                                                        <div class='sidebar-scale-body__item' {accidentsMonthOnClick_zndh} data-scale-item-count='{accidentsMonth_zndh}' data-scale-item-title='ЗНДХ'>
                                                            <div class='sidebar-scale-level'>
                                                                <span class='sidebar-scale-level--{accidentsMonthColor_zndh}' style='height: {accidentsMonthHeight_zndh}%;' data-responsive-value='{accidentsMonthHeight_zndh}%'></span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class='sidebar-scale-body {accidentsYearCursor}' {accidentsYearOnClick} id='sidebarScaleDataYear' style='display: flex;'>
                                                        <div class='sidebar-scale-body__item' {accidentsYearOnClick_rvp} data-scale-item-count='{accidentsYear_rvp}' data-scale-item-title='РВП'>
                                                            <div class='sidebar-scale-level'>
                                                                <span class='sidebar-scale-level--{accidentsYearColor_rvp}' style='height: {accidentsYearHeight_rvp}%;' data-responsive-value='{accidentsYearHeight_rvp}%'></span>
                                                            </div>
                                                        </div>
                                                        <div class='sidebar-scale-body__item' {accidentsYearOnClick_znds} data-scale-item-count='{accidentsYear_znds}' data-scale-item-title='ЗНДС'>
                                                            <div class='sidebar-scale-level'>
                                                                <span class='sidebar-scale-level--{accidentsYearColor_znds}' style='height: {accidentsYearHeight_znds}%;' data-responsive-value='{accidentsYearHeight_znds}%'></span>
                                                            </div>
                                                        </div>
                                                        <div class='sidebar-scale-body__item' {accidentsYearOnClick_zndh} data-scale-item-count='{accidentsYear_zndh}' data-scale-item-title='ЗНДХ'>
                                                            <div class='sidebar-scale-level'>
                                                                <span class='sidebar-scale-level--{accidentsYearColor_zndh}' style='height: {accidentsYearHeight_zndh}%;' data-responsive-value='{accidentsYearHeight_zndh}%'></span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class='sidebar-scale-footer'>
                                                        <label for='ssMonthStart' class='sidebar-slider'>
                                                            <input type='checkbox' name='data-output-interval' class='ss-radio-js' id='ssMonthStart'>
                                                            <span class='sidebar-slider-slider'></span>
                                                            <span class='sidebar-slider__label'>С начала месяца</span>
                                                        </label>
                                                        <label for='ssWholeYear' class='sidebar-slider'>
                                                            <input type='checkbox' name='data-output-interval' class='ss-radio-js' id='ssWholeYear' checked>
                                                            <span class='sidebar-slider-slider'></span>
                                                            <span class='sidebar-slider__label'>С начала года</span>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class='sidebar-hor-scale'>
                                                    <div class='sidebar-hor-scale-header'>
                                                        <span class='sidebar-hor-scale-header__text'>Изношенность фонда</span>
                                                    </div>
                                                    <div class='sidebar-hor-scale-body'>
                                                        <div class='sidebar-hor-scale-body__item'>
                                                            <div class='sidebar-hor-scale-body__item-title'>РВП</div>
                                                            <div class='sidebar-hor-scale-body__item-scale'>
                                                                <div class='sidebar-hor-scale-body__fill-{ageCurr_color_rvp}' style='width: {ageCurr_width_rvp}%;'></div>
                                                            </div>
                                                            <div class='sidebar-hor-scale-body__item-val'>{ageCurr_rvp}%</div>
                                                        </div>
                                                        <div class='sidebar-hor-scale-body__item'>
                                                            <div class='sidebar-hor-scale-body__item-title'>ЗНДС</div>
                                                            <div class='sidebar-hor-scale-body__item-scale'>
                                                                <div class='sidebar-hor-scale-body__fill-{ageCurr_color_znds}' style='width: {ageCurr_width_znds}%;'></div>
                                                            </div>
                                                            <div class='sidebar-hor-scale-body__item-val'>{ageCurr_znds}%</div>
                                                        </div>
                                                        <div class='sidebar-hor-scale-body__item'>
                                                            <div class='sidebar-hor-scale-body__item-title'>ЗНДХ</div>
                                                            <div class='sidebar-hor-scale-body__item-scale'>
                                                                <div class='sidebar-hor-scale-body__fill-{ageCurr_color_zndh}' style='width: {ageCurr_width_zndh}%;'></div>
                                                            </div>
                                                            <div class='sidebar-hor-scale-body__item-val'>{ageCurr_zndh}%</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                        <div class='main-content'>

                                            <div class='main-chart-wrap'>
                                                <h1 class='main-content__title' style='display: block;'>Мониторинг нефтегазопромыслового оборудования</h1>
                                                <div id='mainChart-all' class='main-chart main-chart-level' style='display: block; height: 260px; width: 100%;'></div>
                                            </div>

                                            <div class='main-content-data' id='main-content-data-all'>
                                                <div class='main-content-data-wrap'>
                                                    <div class='main-content-data-wrap__item'>
                                                        <div class='small-chart-list small-chart-list--year'>
                                                            <div class='small-chart-list__item'>
                                                                <span class='small-chart-list__title'>Состояние парка оборудования</span>
                                                                <ul class='small-chart-list-legend'>
                                                                    <li id='in-work' class='small-chart-list-legend__item small-chart-list-legend__item--green'>В работе</li>
                                                                    <li id='in-reserve' class='small-chart-list-legend__item small-chart-list-legend__item--blue'>В резерве</li>
                                                                    <li id='in-repair' class='small-chart-list-legend__item small-chart-list-legend__item--red'>В ремонте</li>
                                                                    <li id='preverv' class='small-chart-list-legend__item small-chart-list-legend__item--yellow'>Выведено из эксплуатации</li>
                                                                </ul>
                                                            </div>
                                                            <div class='small-chart-list__item {stateEquipDynCursor}' {stateEquipDynOnClick}>
                                                                <div id='pieBigEquipment' style='width: 100%; height: 180px;'></div>
                                                            </div>
                                                            <div class='small-chart-list__item {stateEquipStatCursor}' {stateEquipStatOnClick}>
                                                                <div id='pieBigPipeline' style='width: 100%; height: 180px;'></div>
                                                            </div>
                                                            <div class='small-chart-list__item {ageCompCursor}' {ageCompOnClick}>
                                                                <div class='sidebar-scale sidebar-scale--big sidebar-scale--light'>
                                                                    <div class='sidebar-scale-header sidebar-scale-header--chart-style'>
                                                                        <span class='sidebar-scale-header__text'>Возрастной состав оборудования</span>
                                                                    </div>
                                                                    <div class='sidebar-scale-body'>
                                                                        <div class='sidebar-scale-body__item {ageComp1Cursor}' {ageComp1OnClick} data-scale-item-count='{ageComp_meh_1}%' data-scale-item-title='< 1 г.'>
                                                                            <div class='sidebar-scale-level'>
                                                                                <span class='sidebar-scale-level--green' style='height: {ageComp_meh_1_width}%;'></span>
                                                                            </div>
                                                                        </div>
                                                                        <div class='sidebar-scale-body__item {ageComp2Cursor}' {ageComp2OnClick} data-scale-item-count='{ageComp_meh_2}%' data-scale-item-title='< 3 л.'>
                                                                            <div class='sidebar-scale-level'>
                                                                                <span class='sidebar-scale-level--violet' style='height: {ageComp_meh_2_width}%;'></span>
                                                                            </div>
                                                                        </div>
                                                                        <div class='sidebar-scale-body__item {ageComp3Cursor}' {ageComp3OnClick} data-scale-item-count='{ageComp_meh_3}%' data-scale-item-title='< 5 л.'>
                                                                            <div class='sidebar-scale-level'>
                                                                                <span class='sidebar-scale-level--blue' style='height: {ageComp_meh_3_width}%;'></span>
                                                                            </div>
                                                                        </div>
                                                                        <div class='sidebar-scale-body__item {ageComp4Cursor}' {ageComp4OnClick} data-scale-item-count='{ageComp_meh_4}%' data-scale-item-title='< 10 л.'>
                                                                            <div class='sidebar-scale-level'>
                                                                                <span class='sidebar-scale-level--yellow' style='height: {ageComp_meh_4_width}%;'></span>
                                                                            </div>
                                                                        </div>
                                                                        <div class='sidebar-scale-body__item {ageComp5Cursor}' {ageComp5OnClick} data-scale-item-count='{ageComp_meh_5}%' data-scale-item-title='< 15 л.'>
                                                                            <div class='sidebar-scale-level'>
                                                                                <span class='sidebar-scale-level--orange' style='height: {ageComp_meh_5_width}%;'></span>
                                                                            </div>
                                                                        </div>
                                                                        <div class='sidebar-scale-body__item {ageComp6Cursor}' {ageComp6OnClick} data-scale-item-count='{ageComp_meh_6}%' data-scale-item-title='< 20 л.'>
                                                                            <div class='sidebar-scale-level'>
                                                                                <span class='sidebar-scale-level--brown' style='height: {ageComp_meh_6_width}%;'></span>
                                                                            </div>
                                                                        </div>
                                                                        <div class='sidebar-scale-body__item {ageComp7Cursor}' {ageComp7OnClick} data-scale-item-count='{ageComp_meh_7}%' data-scale-item-title='> 20 л.'>
                                                                            <div class='sidebar-scale-level'>
                                                                                <span class='sidebar-scale-level--red' style='height: {ageComp_meh_7_width}%;'></span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class='main-content-data-wrap__item'>
                                                        <div class='small-chart-list small-chart-list--dark'>
                                                            
                                                            <div class='small-chart-list__item'>
                                                                <span class='small-chart-list__title'>Выполнение плана ТОиР</span>
                                                                <ul class='small-chart-list-legend'>
                                                                    <li class='small-chart-list-legend__item small-chart-list-legend__item--blue'>Выполнено ТОиР</li>
                                                                    <li class='small-chart-list-legend__item small-chart-list-legend__item--red'>Не выполнено ТОиР</li>
                                                                </ul>
                                                            </div>

                                                            <div class='small-chart-list__item {toirYearCursor}' {toirYearOnClick}>
                                                                <div id='dashboardLinePlanToirYear' style='width: 100%; height: 180px; display: none;'></div>
                                                                <div class='dashboard-progress-bar-wrap cno-mc-pos-cc' id='progressBarWrapYear' data-progress-bar-title='За год'>
                                                                    <div class='dashboard-progress-bar__title'>
                                                                        <span class='dashboard-success-percentage'>{toirMeh_year}%</span>
                                                                        <span class='dashboard-success-percentage__text'> — выполнено</span>
                                                                    </div>
                                                                    <div class='dashboard-progress-bar' data-progress-percentage='{toirMeh_yearCount}'>
                                                                        <div class='dashboard-progress-bar__fill dashboard-progress-bar__fill--blue' style='width: {toirMeh_year}%;'></div>
                                                                    </div>
                                                                    <div class='dashboard-progress-bar' data-progress-percentage='{toirMeh_yearCount_undone}'>
                                                                        <div class='dashboard-progress-bar__fill dashboard-progress-bar__fill--red' style='width: {toirMeh_year_undone}%;'></div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class='small-chart-list__item {toirMonthCursor}' {toirMonthOnClick}>
                                                                <div id='dashboardLinePlanToirMonth' style='width: 100%; height: 180px; display: none;'></div>
                                                                <div class='dashboard-progress-bar-wrap cno-mc-pos-cc' id='progressBarWrapMonth' data-progress-bar-title='За месяц'>
                                                                    <div class='dashboard-progress-bar__title'>
                                                                        <span class='dashboard-success-percentage'>{toirMeh_month}%</span>
                                                                        <span class='dashboard-success-percentage__text'> — выполнено</span>
                                                                    </div>
                                                                    <div class='dashboard-progress-bar' data-progress-percentage='{toirMeh_monthCount}'>
                                                                        <div class='dashboard-progress-bar__fill dashboard-progress-bar__fill--blue' style='width: {toirMeh_month}%;'></div>
                                                                    </div>
                                                                    <div class='dashboard-progress-bar' data-progress-percentage='{toirMeh_monthCount_undone}'>
                                                                        <div class='dashboard-progress-bar__fill dashboard-progress-bar__fill--red' style='width: {toirMeh_month_undone}%;'></div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class='small-chart-list__item solidgauge-radial-bg--large {riskMatrCursor}' {riskMatrOnClick}>
                                                                <div id='dashboardGaugePlanToir' style='width: 180px; height: 180px;'></div>
                                                            </div>

                                                        </div>
                                                    </div>
                                                    <div class='main-content-data-wrap__item'>
                                                        <div class='small-chart-list small-chart-list--light'>
                                                            <div class='small-chart-list__item'>
                                                                <span class='small-chart-list__title'>Наполнение системы</span>
                                                                <h4 class='small-chart-list__sys-capab'>{fill_meh_1}%</h4>
                                                            </div>
                                                            <div class='small-chart-list__item {fillMeh2Cursor}' {fillMeh2OnClick}>
                                                                <div class='cno-mc-pos-cc solidgauge-radial-bg' id='gaugeZavPas' style='width: 100%; height: 140px;'></div>
                                                            </div>
                                                            <div class='small-chart-list__item {fillMeh3Cursor}' {fillMeh3OnClick}>
                                                                <div class='cno-mc-pos-cc solidgauge-radial-bg' id='gaugePokNadej' style='width: 100%; height: 140px;'></div>
                                                            </div>
                                                            <div class='small-chart-list__item {fillMeh4Cursor}' {fillMeh4OnClick}>
                                                                <div class='cno-mc-pos-cc solidgauge-radial-bg' id='gaugeToir' style='width: 100%; height: 140px;'></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class='main-economics-chart-wrap' data-economics-block-title='Блок экономики'>
                                                    <div id='economicsChart_TO' class='main-economics-chart'></div>
                                                    <div id='economicsChart_TR' class='main-economics-chart'></div>
                                                    <div id='economicsChart_KP' class='main-economics-chart'></div>
                                                    <div id='economicsChart_DIAG' class='main-economics-chart'></div>
                                                    <div id='economicsChart_ONVSS' class='main-economics-chart'></div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </main>
                            </div>
                            ",
                        "</tpl>",
                    ],
                ],
        ];
        return $m;
    }

    function getWsFunc($v)
    {
        $dataForWsFunc = $this->dataArray;
        $access_matr = 'allow';
        $m['init'] = "function(ths,panel,val){
            ws_panelFunc(panel,'DASHBOARD_PAGE_SCRIPTS',val);

            ws_panelFunc(panel,'DASHBOARD_MAIN_GAUGE_ZAV_PAS',val);
            ws_panelFunc(panel,'DASHBOARD_MAIN_GAUGE_POK_NADEJ',val);
            ws_panelFunc(panel,'DASHBOARD_MAIN_GAUGE_TOIR',val);

            ws_panelFunc(panel,'DASHBOARD_MAIN_GAUGE_PLAN_TOIR',val);
            ws_panelFunc(panel,'DASHBOARD_LINE_PLAN_TOIR_MONTH',val);
            ws_panelFunc(panel,'DASHBOARD_LINE_PLAN_TOIR_YEAR',val);
            ws_panelFunc(panel,'DASHBOARD_LINE_PLAN_TOIR',val);

            ws_panelFunc(panel,'DASHBOARD_MAIN_LINE_ALL',val);
            ws_panelFunc(panel,'PIE_BIG_EQUIPMENT',val);
            ws_panelFunc(panel,'PIE_BIG_PIPELINE',val);

            ws_panelFunc(panel,'ECONOMICSCHART_TO',val);
            ws_panelFunc(panel,'ECONOMICSCHART_TR',val);
            ws_panelFunc(panel,'ECONOMICSCHART_KP',val);
            ws_panelFunc(panel,'ECONOMICSCHART_DIAG',val);
            ws_panelFunc(panel,'ECONOMICSCHART_ONVSS',val);
            if ('" . $dataForWsFunc['flag_update'] . "' == true) {
                val = val || {};
                val.view= ws_panelGetFieldsByName(ths,'cnoDashboardZn');
                var func = {
                    processing: function(val,processData){
                        ws_panelEmbed({embedType:'region',parentId:'cno_startDashboardPanel_container',region:'center'},['md','" . $this->getModuleCode() . "', 'panel','" . $this->getPanelCode() . "', 'get'],val,func);
                    },
                    callback:function (val) {
                        if (val.taskRec) ws_task_setStatus(val.taskRec.data.id, 1);
                    }
                };
                val.maskPanel=panel;
                var value = {};
                value.datetime = val.datetime;
                value.obj_idM = '" . ws_jsonEncode($dataForWsFunc['obj_idM']) . "';
                value.access = '" . $access_matr . "';
                ws_request(['md','" . $this->getModuleCode() . "','panel','" . $this->getPanelCode() . "','update',ws_udd(value)],val,func);
            }
        }";
        //begin ws_panelFunc
        $m['equipStateStat'] = "function(ths,panel,val){
            val=val || {};
            var func = {
                ok: function(val){
                    ws_panelEmbed({embedType:'win',modal:true,maximized:true,maximizable:false,  width:1300, height:800},['md','reportCno','panel','main','get',{obj_idM:'" . ws_jsonEncode($dataForWsFunc['obj_idM']) . "','obj_id':val.obj_id,'date':val.date,report_id: '" . $dataForWsFunc['ws_func']['reportEquipStateStat'] . "'}],{},{});
                },
                error:function (val) {
                    Ext.Msg.show({
                    title:'" . $this->t("Состояние парка статического оборудования") . "',
                    msg:'" . $this->t("Отсутствуют данные за указанное число") . "!',
                    icon:Ext.Msg.WARNING,
                    buttons:Ext.Msg.OK
                    });
                },
            };
            var value={};
            value.date = val.date;
            value.obj_id = val.obj_id;
            value.obj_idM = '" . ws_jsonEncode($dataForWsFunc['obj_idM']) . "';
            ws_request(['md','" . $this->getModuleCode() . "','panel','" . $this->getPanelCode() . "','equipStateStat',ws_udd(value)],val,func,{mask:false});
        }";
        $m['equipStateDyn'] = "function(ths,panel,val){
            val=val || {};
            var func = {
                ok: function(val){
                    ws_panelEmbed({embedType:'win',modal:true,maximized:true,maximizable:false,  width:1300, height:800},['md','reportCno','panel','main','get',{obj_idM:'" . ws_jsonEncode($dataForWsFunc['obj_idM']) . "','obj_id':val.obj_id,'date':val.date,report_id: '" . $dataForWsFunc['ws_func']['reportEquipStateDyn'] . "'}],{},{});
                },
                error:function (val) {
                    Ext.Msg.show({
                    title:'" . $this->t("Состояние парка динамического оборудования") . "',
                    msg:'" . $this->t("Отсутствуют данные за указанное число") . "!',
                    icon:Ext.Msg.WARNING,
                    buttons:Ext.Msg.OK
                    });
                },
            };
            var value={};
            value.date = val.date;
            value.obj_id = val.obj_id;
            value.obj_idM = '" . ws_jsonEncode($dataForWsFunc['obj_idM']) . "';
            ws_request(['md','" . $this->getModuleCode() . "','panel','" . $this->getPanelCode() . "','equipStateDyn',ws_udd(value)],val,func,{mask:false});
        }";
        $m['accidentsYear'] = "function(ths,panel,val){
            val=val || {};
            ws_panelEmbed({embedType:'win',modal:true,maximized:true,maximizable:false,  width:1500, height:800},['md','reportCno','panel','main','get',{do:val.do,obj_idM:'" . ws_jsonEncode($dataForWsFunc['obj_idM']) . "','obj_id':val.obj_id,'date':val.date,report_id: '" . $dataForWsFunc['ws_func']['reportAccidentsYear'] . "'}],{},{});
        }";
        $m['accidentsMonth'] = "function(ths,panel,val){
            val=val || {};
            ws_panelEmbed({embedType:'win',modal:true,maximized:true,maximizable:false,  width:1500, height:800},['md','reportCno','panel','main','get',{do:val.do,obj_idM:'" . ws_jsonEncode($dataForWsFunc['obj_idM']) . "','obj_id':val.obj_id,'date':val.date,report_id: '" . $dataForWsFunc['ws_func']['reportAccidentsMonth'] . "'}],{},{});
        }";
        $m['toirYear'] = "function(ths,panel,val){
            val=val || {};
            var func = {
                ok: function(val){
                    ws_panelEmbed({embedType:'win',modal:true,maximized:true,maximizable:false, width:1600, height:800},['md','reportCno','panel','main','get',{obj_idM:'" . ws_jsonEncode($dataForWsFunc['obj_idM']) . "','obj_id':val.obj_id,'date':val.date,report_id: '" . $dataForWsFunc['ws_func']['reportToirYear'] . "'}],{},{});
                },
                error:function (val) {
                    Ext.Msg.show({
                    title:'" . $this->t("Выполнение ТОиР за год") . "',
                    msg:'" . $this->t("Отсутствуют данные за указанное число") . "!',
                    icon:Ext.Msg.WARNING,
                    buttons:Ext.Msg.OK
                    });
                },
            };
            var value={};
            value.date = val.date;
            value.obj_id = val.obj_id;
            value.obj_idM = '" . ws_jsonEncode($dataForWsFunc['obj_idM']) . "';
            ws_request(['md','" . $this->getModuleCode() . "','panel','" . $this->getPanelCode() . "','toirYear',ws_udd(value)],val,func,{mask:false});
        }";
        $m['toirMonthGroup'] = "function(ths,panel,val){
            val=val || {};
            var func = {
                ok: function(val){
                    ws_panelEmbed({embedType:'win',modal:true,maximized:true,maximizable:false, width:1600, height:800},['md','cno','panel','toirMonthGroupPanel','get',{obj_idM:'" . ws_jsonEncode($dataForWsFunc['obj_idM']) . "','obj_id':val.obj_id,'date':val.date,report_id: '" . $dataForWsFunc['ws_func']['reportToirMonth'] . "'}],{},{});
                },
                error:function (val) {
                    Ext.Msg.show({
                    title:'" . $this->t("Выполнение ТОиР за месяц") . "',
                    msg:'" . $this->t("Отсутствуют данные за указанное число") . "!',
                    icon:Ext.Msg.WARNING,
                    buttons:Ext.Msg.OK
                    });
                },
            };
            var value={};
            value.date = val.date;
            value.obj_id = val.obj_id;
            value.obj_idM = '" . ws_jsonEncode($dataForWsFunc['obj_idM']) . "';
            ws_request(['md','" . $this->getModuleCode() . "','panel','" . $this->getPanelCode() . "','toirMonth',ws_udd(value)],val,func,{mask:false});
        }";
        $m['toirMonth'] = "function(ths,panel,val){
            val=val || {};
            var func = {
                ok: function(val){
                    ws_panelEmbed({embedType:'win',modal:true,maximized:true,maximizable:false, width:1600, height:800},['md','reportCno','panel','main','get',{obj_idM:'" . ws_jsonEncode($dataForWsFunc['obj_idM']) . "','obj_id':val.obj_id,'date':val.date,report_id: '" . $dataForWsFunc['ws_func']['reportToirMonth'] . "'}],{},{});
                },
                error:function (val) {
                    Ext.Msg.show({
                    title:'" . $this->t("Выполнение ТОиР за месяц") . "',
                    msg:'" . $this->t("Отсутствуют данные за указанное число") . "!',
                    icon:Ext.Msg.WARNING,
                    buttons:Ext.Msg.OK
                    });
                },
            };
            var value={};
            value.date = val.date;
            value.obj_id = val.obj_id;
            value.obj_idM = '" . ws_jsonEncode($dataForWsFunc['obj_idM']) . "';
            ws_request(['md','" . $this->getModuleCode() . "','panel','" . $this->getPanelCode() . "','toirMonth',ws_udd(value)],val,func,{mask:false});
        }";
        $m['ageComp'] = "function(ths,panel,val){
            val=val || {};
            var func = {
                ok: function(val){
                    ws_panelEmbed({embedType:'win',modal:true,maximized:true,maximizable:false,  width:1400, height:800},['md','reportCno','panel','main','get',{obj_idM:'" . ws_jsonEncode($dataForWsFunc['obj_idM']) . "','type':val.type,'obj_id':val.obj_id,'date':val.date,report_id: '" . $dataForWsFunc['ws_func']['reportAgeComp'] . "'}],{},{});
                },
                error:function (val) {
                    Ext.Msg.show({
                    title:'" . $this->t("Возрастной состав оборудования") . "',
                    msg:'" . $this->t("Отсутствуют данные за указанное число") . "!',
                    icon:Ext.Msg.WARNING,
                    buttons:Ext.Msg.OK
                    });
                },
            };
            var value={};
            value.date = val.date;
            value.obj_id = val.obj_id;
            value.obj_idM = '" . ws_jsonEncode($dataForWsFunc['obj_idM']) . "';
            value.type = val.type;
            ws_request(['md','" . $this->getModuleCode() . "','panel','" . $this->getPanelCode() . "','ageComp',ws_udd(value)],val,func,{mask:false});
            event.stopPropagation();
        }";
        $m['fillMeh2'] = "function(ths,panel,val){
            val=val || {};
            var func = {
                ok: function(val){
                    ws_panelEmbed({embedType:'win',modal:true,maximized:true,maximizable:false,  width:1400, height:800},['md','reportCno','panel','main','get',{obj_idM:'" . ws_jsonEncode($dataForWsFunc['obj_idM']) . "','obj_id':val.obj_id,'date':val.date,report_id: '" . $dataForWsFunc['ws_func']['reportFillMeh2'] . "'}],{},{});
                },
                error:function (val) {
                    Ext.Msg.show({
                    title:'" . $this->t("Состояние парка динамического оборудования") . "',
                    msg:'" . $this->t("Отсутствуют данные за указанное число") . "!',
                    icon:Ext.Msg.WARNING,
                    buttons:Ext.Msg.OK
                    });
                },
            };
            var value={};
            value.date = val.date;
            value.obj_id = val.obj_id;
            value.obj_idM = '" . ws_jsonEncode($dataForWsFunc['obj_idM']) . "';
            ws_request(['md','" . $this->getModuleCode() . "','panel','" . $this->getPanelCode() . "','fillMeh2',ws_udd(value)],val,func,{mask:false});
        }";
        $m['fillMeh3'] = "function(ths,panel,val){
            val=val || {};
            var func = {
                ok: function(val){
                    ws_panelEmbed({embedType:'win',modal:true,maximized:true,maximizable:false,  width:1400, height:800},['md','reportCno','panel','main','get',{obj_idM:'" . ws_jsonEncode($dataForWsFunc['obj_idM']) . "','obj_id':val.obj_id,'date':val.date,report_id: '" . $dataForWsFunc['ws_func']['reportFillMeh3'] . "'}],{},{});
                },
                error:function (val) {
                    Ext.Msg.show({
                    title:'" . $this->t("Состояние парка динамического оборудования") . "',
                    msg:'" . $this->t("Отсутствуют данные за указанное число") . "!',
                    icon:Ext.Msg.WARNING,
                    buttons:Ext.Msg.OK
                    });
                },
            };
            var value={};
            value.date = val.date;
            value.obj_id = val.obj_id;
            value.obj_idM = '" . ws_jsonEncode($dataForWsFunc['obj_idM']) . "';
            ws_request(['md','" . $this->getModuleCode() . "','panel','" . $this->getPanelCode() . "','fillMeh3',ws_udd(value)],val,func,{mask:false});
        }";
        $m['fillMeh4'] = "function(ths,panel,val){
            val=val || {};
            var func = {
                ok: function(val){
                    ws_panelEmbed({embedType:'win',modal:true,maximized:true,maximizable:false,  width:1400, height:800},['md','reportCno','panel','main','get',{obj_idM:'" . ws_jsonEncode($dataForWsFunc['obj_idM']) . "','obj_id':val.obj_id,'date':val.date,report_id: '" . $dataForWsFunc['ws_func']['reportFillMeh4'] . "'}],{},{});
                },
                error:function (val) {
                    Ext.Msg.show({
                    title:'" . $this->t("Состояние парка динамического оборудования") . "',
                    msg:'" . $this->t("Отсутствуют данные за указанное число") . "!',
                    icon:Ext.Msg.WARNING,
                    buttons:Ext.Msg.OK
                    });
                },
            };
            var value={};
            value.date = val.date;
            value.obj_id = val.obj_id;
            value.obj_idM = '" . ws_jsonEncode($dataForWsFunc['obj_idM']) . "';
            ws_request(['md','" . $this->getModuleCode() . "','panel','" . $this->getPanelCode() . "','fillMeh4',ws_udd(value)],val,func,{mask:false});
        }";
        $m['riskMatr'] = "function(ths,panel,val){
            val=val || {};
            var func = {
                ok: function(val){
                    ws_panelEmbed({embedType:'win',modal:true},['md','cno','panel','treeMapRiskPanelDash','get',{'code':'org_zarubezh','obj_id':val.obj_id,'date':val.date,'maket':'zn'}],{},{});
                },
                error:function (val) {
                    Ext.Msg.show({
                    title:'" . $this->t("Вероятность отказа") . "',
                    msg:'" . $this->t("Отсутствуют данные за указанное число") . "!',
                    icon:Ext.Msg.WARNING,
                    buttons:Ext.Msg.OK
                    });
                },
            };
            var value={};
            value.date = val.date;
            value.obj_id = val.obj_id;
            ws_request(['md','" . $this->getModuleCode() . "','panel','" . $this->getPanelCode() . "','riskMatr',ws_udd(value)],val,func,{mask:false});
        }";
        $m['changeDate'] = "function(ths,panel,val){
            val = val || {};
            val.view= ws_panelGetFieldsByName(ths,'cnoDashboardZn');
            var func = {
                processing: function(val,processData){
                    var store= val.view.getStore();
                    store.removeAll();
                    store.loadData(processData.data,true);
                    
                    var data = {};
                    
                    if ( typeof ws_getWSData(panel).dataSource != 'undefined' ) {
                        data.dataSource = ws_getWSData(panel).dataSource;
                    }
                    if ( typeof ws_getWSData(panel).radioSidebarMonth != 'undefined' ) {
                        data.radioSidebarMonth = ws_getWSData(panel).radioSidebarMonth;
                    }
                    if ( typeof ws_getWSData(panel).radioSidebarYear != 'undefined' ) {
                        data.radioSidebarYear = ws_getWSData(panel).radioSidebarYear;
                    }
                    if ( typeof ws_getWSData(panel).radioContentMonth != 'undefined' ) {
                        data.radioContentMonth = ws_getWSData(panel).radioContentMonth;
                    }
                    if ( typeof ws_getWSData(panel).radioContentYear != 'undefined' ) {
                        data.radioContentYear = ws_getWSData(panel).radioContentYear;
                    }

                    data.datetime = val.datetime;
                    ws_panelFunc(val.maskPanel,'DASHBOARD_PAGE_SCRIPTS', data);
            
                    ws_panelFunc(panel,'DASHBOARD_MAIN_GAUGE_ZAV_PAS',{data:processData.dataGauge.systemFill});
                    ws_panelFunc(panel,'DASHBOARD_MAIN_GAUGE_POK_NADEJ',{data:processData.dataGauge.systemFill});
                    ws_panelFunc(panel,'DASHBOARD_MAIN_GAUGE_TOIR',{data:processData.dataGauge.systemFill});
                    
                    ws_panelFunc(panel,'DASHBOARD_MAIN_GAUGE_PLAN_TOIR',{data:processData.dataGauge.risk});
                    ws_panelFunc(panel,'DASHBOARD_LINE_PLAN_TOIR_MONTH',{data:processData.dataGauge.toir, datetime:val.datetime});
                    ws_panelFunc(panel,'DASHBOARD_LINE_PLAN_TOIR_YEAR',{data:processData.dataGauge.toir, datetime:val.datetime});
                    ws_panelFunc(panel,'DASHBOARD_LINE_PLAN_TOIR',{data:processData.dataGauge.toir});
                    
                    ws_panelFunc(panel,'DASHBOARD_MAIN_LINE_ALL',{data:processData.dataGauge.safetyDyn});
                    
                    ws_panelFunc(panel,'PIE_BIG_EQUIPMENT',{data:processData.dataGauge.stateEquipMeh});
                    ws_panelFunc(panel,'PIE_BIG_PIPELINE',{data:processData.dataGauge.stateEquipMeh});
                    
                    ws_panelFunc(panel,'ECONOMICSCHART_TO',val);
                    ws_panelFunc(panel,'ECONOMICSCHART_TR',val);
                    ws_panelFunc(panel,'ECONOMICSCHART_KP',val);
                    ws_panelFunc(panel,'ECONOMICSCHART_DIAG',val);
                    ws_panelFunc(panel,'ECONOMICSCHART_ONVSS',val);
                },
                callback:function (val) {
                    ws_panel_unmask(val.maskPanel);
                    if (val.taskRec) ws_task_setStatus(val.taskRec.data.id, 1);
                }
            };
            val.maskPanel=panel;
            ws_panel_mask(val.maskPanel);
            var value = {};
            value.datetime = val.datetime;
            value.obj_idM = '" . ws_jsonEncode($dataForWsFunc['obj_idM']) . "';
            value.access = '" . $access_matr . "';
            ws_request(['md','" . $this->getModuleCode() . "','panel','" . $this->getPanelCode() . "','update',ws_udd(value)],val,func);
        }";
        //end ws_panelFunc
        // begin DASHBOARD_SIDEBAR_CALEND
        $m['DASHBOARD_PAGE_SCRIPTS'] = "function (ths, panel, val) {
        val = val || {};
        
        if (typeof val.datetime != 'undefined'){
            var datetime = val.datetime;
        }
            jQuery(function ($) {
                $.datepicker.regional['ru'] = {
                    closeText: 'Закрыть',
                    prevText: '&lt;',
                    nextText: '&gt;',
                    currentText: 'Сегодня',
                    monthNames: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
                    'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
                    monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
                    'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
                    dayNames: ['воскресенье', 'понедельник', 'вторник', 'среда', 'четверг', 'пятница', 'суббота'],
                    dayNamesShort: ['вск', 'пнд', 'втр', 'срд', 'чтв', 'птн', 'сбт'],
                    dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
                    weekHeader: 'Нед',
                    dateFormat: 'dd.mm.yy',
                    firstDay: 1,
                    isRTL: false,
                    showMonthAfterYear: false,
                    yearSuffix: ''
                };
                $.datepicker.setDefaults($.datepicker.regional['ru']);
            });
            $( '#dashboardDatepicker' ).datepicker({
                defaultDate: datetime,
                maxDate: '+0d', 
                minDate: new Date(2018, 5, 29), 
                navigationAsDateFormat: true,
                nextText: 'MM',
                prevText: 'MM',
                onSelect: function(dateText) {
                    ws_panelFunc(panel,'changeDate',{datetime:this.value});
                }
            });

            function selectDataSource(dataType) {
                var itemsEl = $('.main-content-list-indicators__item');
                var titleDefStr = 'Мониторинг ';
                var titleArr = [
                    'нефтегазопромыслового оборудования',
                    '«Механика»',
                    '«Энергетика»',
                    '«Метрология»'
                ];
                var itemsLen = itemsEl.length;

                var mainContentId = 'main-content-data-';
                var newMainContentId = mainContentId + dataType;

                var mainSidebarId = 'main-sidebar-data-';
                var newMainSidebarId = mainSidebarId + dataType;

                var mainChartId = 'mainChart-';
                var newMainChartId = mainChartId + dataType;

                $(itemsEl).removeClass('active');

                switch(dataType) {
                    case 'all':
                        $('.main-content__title').text(titleDefStr + titleArr[0]);
                        break;
                    case 'mech':
                        $('.main-content__title').text(titleDefStr + titleArr[1]);
                        break;
                    case 'ener':
                        $('.main-content__title').text(titleDefStr + titleArr[2]);
                        break;
                    case 'metro':
                        $('.main-content__title').text(titleDefStr + titleArr[3]);
                        break;
                    default:
                        $('.main-content__title').text(titleDefStr + titleArr[0]);
                        break;
                };

                for (var i = 0; i < itemsLen; i++) {
                    var allDataType = $(itemsEl).eq(i).attr('data-target');

                    if (dataType == allDataType) {
                        $(itemsEl).eq(i).addClass('active');
                        $('#' + newMainChartId).css('display', 'block');
                        $('#' + newMainContentId).fadeIn('fast');
                        $('#' + newMainSidebarId).fadeIn('fast');
                    } else {
                        $('#' + mainChartId + allDataType).css('display', 'none');
                        $('#' + mainContentId + allDataType).fadeOut('fast');
                        $('#' + mainSidebarId + allDataType).fadeOut('fast');
                    }
                };
            };

            function selectDataSourceByDate(dataType) {
                var itemsEl = $('.main-content-list-indicators__item');
                var titleDefStr = 'Мониторинг ';
                var titleArr = [
                    'нефтегазопромыслового оборудования',
                    '«Механика»',
                    '«Энергетика»',
                    '«Метрология»'
                ];
                var itemsLen = itemsEl.length;

                var mainContentId = 'main-content-data-';
                var newMainContentId = mainContentId + dataType;

                var mainSidebarId = 'main-sidebar-data-';
                var newMainSidebarId = mainSidebarId + dataType;

                var mainChartId = 'mainChart-';
                var newMainChartId = mainChartId + dataType;

                $(itemsEl).removeClass('active');

                switch(dataType) {
                    case 'all':
                        $('.main-content__title').text(titleDefStr + titleArr[0]);
                        break;
                    case 'mech':
                        $('.main-content__title').text(titleDefStr + titleArr[1]);
                        break;
                    case 'ener':
                        $('.main-content__title').text(titleDefStr + titleArr[2]);
                        break;
                    case 'metro':
                        $('.main-content__title').text(titleDefStr + titleArr[3]);
                        break;
                    default:
                        $('.main-content__title').text(titleDefStr + titleArr[0]);
                        break;
                };

                for (var i = 0; i < itemsLen; i++) {
                    var allDataType = $(itemsEl).eq(i).attr('data-target');

                    if (dataType == allDataType) {
                        $(itemsEl).eq(i).addClass('active');
                        $('#' + newMainChartId).css('display', 'block');
                        $('#' + newMainContentId).css('display', 'block');
                        $('#' + newMainSidebarId).css('display', 'block');
                    } else {
                        $('#' + mainChartId + allDataType).css('display', 'none');
                        $('#' + mainContentId + allDataType).css('display', 'none');
                        $('#' + mainSidebarId + allDataType).css('display', 'none');
                    }
                };
            };
            
            if (typeof val.dataSource != 'undefined'){
                selectDataSourceByDate( val.dataSource );
            }
            
            $('.main-content-list-indicators__item').on('click', function() {
                if ( $(this).attr('data-state') === 'enabled' ) {
                    selectDataSource( $(this).attr('data-target') );
                    ws_setWSData(panel,'dataSource',$(this).attr('data-target'));
                }
            });

            $(window).on('resize unload', function() {
                var el = $('#incedentScaleResponsive .sidebar-scale-level');
                var eLength = el.length;
                if ( $(window).width() <= 1366 ) {
                    for (var i = 0; i < eLength; i++) {
                        var val = $(el).eq(i).children().attr('data-responsive-value');
                        $(el).eq(i).children().css('width', val);
                    }
                } else {
                    for (var i = 0; i < eLength; i++) {
                        $(el).eq(i).children().css('width', '100%');
                    }
                }
                var mainChartHeight = $('.cno-dashboard .main-chart-wrap').css('height');
                $('.main-content-data-wrap').css('height', 'calc(100% - ' + mainChartHeight + ')');
            });

            var mainChartHeight = $('.cno-dashboard .main-chart-wrap').css('height');
            $('.main-content-data-wrap').css('height', 'calc(100% - ' + mainChartHeight + ')');

            /* begin sidebar */

            var monthSwitcherObj = $('#ssMonthStart');
            var yearSwitcherObj = $('#ssWholeYear');
            var lastSwitched = '';

            function imitateRadio(lastId) {
                var monthSwitcherState = monthSwitcherObj.is(':checked');
                var yearSwitcherState = yearSwitcherObj.is(':checked');
                if ((monthSwitcherState && yearSwitcherState) || monthSwitcherState === true || yearSwitcherState === true) {
                    if (monthSwitcherState) {
                        $('#sidebarScaleDataMonth').css('display', 'flex');
                        $('#sidebarScaleDataYear').css('display', 'none');
                    } else if (yearSwitcherState) {
                        $('#sidebarScaleDataYear').css('display', 'flex');
                        $('#sidebarScaleDataMonth').css('display', 'none');
                    }
                } else if ((monthSwitcherState === false && yearSwitcherState === false)) {
                    if (lastId === 'ssMonthStart') {
                        yearSwitcherObj.prop('checked', true);
                        $('#sidebarScaleDataYear').css('display', 'flex');
                        $('#sidebarScaleDataMonth').css('display', 'none');
                    } else if (lastId === 'ssWholeYear') {
                        monthSwitcherObj.prop('checked', true);
                        $('#sidebarScaleDataMonth').css('display', 'flex');
                        $('#sidebarScaleDataYear').css('display', 'none');
                    } else {
                        $('#sidebarScaleDataYear').css('display', 'flex');
                        $('#sidebarScaleDataMonth').css('display', 'none');
                    }
                }
            };

            $('.ss-radio-js').click(function() {
                var elemID = $(this).attr('id');
                if (elemID === 'ssMonthStart') {
                    $('#ssWholeYear').attr('checked', false);
                } else if (elemID === 'ssWholeYear') {
                    $('#ssMonthStart').attr('checked', false);
                }
            });
            $('#ssMonthStart, #ssWholeYear').change(function() {
                lastSwitched = $(this).attr('id');
                imitateRadio(lastSwitched);
                ws_setWSData( panel, 'radioSidebarMonth', $( '#ssMonthStart' ).is(':checked') );
                ws_setWSData( panel, 'radioSidebarYear', $( '#ssWholeYear' ).is(':checked') );
            });
            
            if ( typeof val.radioSidebarMonth != 'undefined' ) {
                getRadioStateSidebarMonth( val.radioSidebarMonth );
            }

            if ( typeof val.radioSidebarYear != 'undefined' ) {
                getRadioStateSidebarYear( val.radioSidebarYear );
            }

            function getRadioStateSidebarMonth( val ) {
                elem = '#ssMonthStart';
                if ( typeof val !== undefined ) {
                    $( elem ).prop('checked', val);
                }
            };
            function getRadioStateSidebarYear( val ) {
                elem = '#ssWholeYear';
                if ( typeof val !== undefined ) {
                    $( elem ).prop('checked', val);
                }
            };

            if ($('#ssWholeYear').is(':checked')) {
                $('#sidebarScaleDataYear').css('display', 'flex');
            } else {
                $('#sidebarScaleDataYear').css('display', 'none');
            }
            if ($('#ssMonthStart').is(':checked')) {
                $('#sidebarScaleDataMonth').css('display', 'flex');
            } else {
                $('#sidebarScaleDataMonth').css('display', 'none');
            };
            
            /* end sidebar */
            
            /* begin content */

            function getYear(v) {
                var d, y, dateArr;
                if (v !== undefined) {
                    dateArr = v.split('.');
                    y = +dateArr[2];
                } else {
                    d = new Date();
                    y = d.getFullYear();
                };
                $('#progressBarWrapYear').attr('data-progress-bar-title', 'За ' + y + ' год');
                return y;
            };
            getYear(val.datetime);
            
            function getMonth(v) {
                var m, mName, d, dateArr;
                if (v !== undefined) {
                    dateArr = v.split('.');
                    m = +dateArr[1];
                } else {
                    d = new Date();
                    m = d.getMonth() + 1;
                };
                switch (m) {
                    case 1:
                        mName = 'Январь';
                        break;
                    case 2:
                        mName = 'Февраль';
                        break;
                    case 3:
                        mName = 'Март';
                        break;
                    case 4:
                        mName = 'Апрель';
                        break;
                    case 5:
                        mName = 'Май';
                        break;
                    case 6:
                        mName = 'Июнь';
                        break;
                    case 7:
                        mName = 'Июль';
                        break;
                    case 8:
                        mName = 'Август';
                        break;
                    case 9:
                        mName = 'Сентябрь';
                        break;
                    case 10:
                        mName = 'Октябрь';
                        break;
                    case 11:
                        mName = 'Ноябрь';
                        break;
                    case 12:
                        mName = 'Декабрь';
                        break;
                };
                $('#progressBarWrapMonth').attr('data-progress-bar-title', 'За ' + mName);
                return mName;
            };
            getMonth(val.datetime);
            /* end content */
        }";
        // end DASHBOARD_SIDEBAR_CALEND
        // begin dashboard main gauge
        $m["DASHBOARD_MAIN_GAUGE_ZAV_PAS"] = "function (ths, panel, val) {
            val = val || {};
            var data = [" . $dataForWsFunc['ws_func']['systemFill']['fill_meh_2'] . "];
            if (typeof val.data != 'undefined'){
                data = [val.data.fill_meh_2];
            }
            var chart_opt = {
                exporting: {
                    enabled: false,
                    url: 'http://127.0.0.1/lib/highcharts/modules/index.php'
                },
                chart: {
                    renderTo: 'gaugeZavPas',
                    type: 'solidgauge',
                    backgroundColor: 'transparent',
                    plotBackgroundColor: 'transparent',
                    borderColor: 'transparent',
                    animation: true
                },
                title: {
                    text: null
                },
                pane: {
                    center: ['50%', '85%'],
                    size: '100%',
                    startAngle: -90,
                    endAngle: 90,
                    background: {
                        backgroundColor: '#fff',
                        borderColor: 'transparent',
                        innerRadius: '60%',
                        outerRadius: '100%',
                        shape: 'arc'
                    }
                },
                tooltip: {
                    enabled: false
                },
                yAxis: {
                    stops: [
                        [0.35, '#F87263'],
                        [0.7, '#FFD148'],
                        [0.71, '#92CF50']
                    ],
                    lineWidth: 0,
                    labels: {
                        enabled: false
                    },
                    min: 0,
                    max: 100,
                    title: {
                        text: 'Заводской паспорт',
                        style: {
                            color: '#8A8A8A',
                            fontFamily: 'PT Sans Regular, sans-serif',
                            fontSize: '12px !important',
                            textTransform: 'uppercase',
                            fontWeight: 'bold'
                        },
                        y: -50
                    },
                    minorTickColor: '#C8CED4',
                    minorTickLength: 4,
                    tickColor: '#C8CED4',
                    tickLength: 3,
                    minorTickPosition: 'inside',
                    tickPosition: 'inside',
                    tickAmount: 1,
                    crosshair: false,
                    offset: -27
                },
                xAxis: {
                    crosshair: false
                },
                colors: ['#4CB4FF'],
                plotOptions: {
                    solidgauge: {
                        dataLabels: {
                            y: 0,
                            borderWidth: 0,
                            useHTML: true,
                            style: {
                                color: '#484B45',
                                fontSize: '19px',
                                fontWeight: 'bold',
                                fontFamily: 'sans-serif'
                            }
                        }
                    }
                },
                series: [{
                    id: 1,
                    data: data,
                    dataLabels: {
                        format: '<div class=\"solid-gauge-datalable\">{y}</div>'
                    }
                }]
            };
            ws_globals['DASHBOARD_MAIN_GAUGE_ZAV_PAS'] = new Highcharts.Chart(chart_opt);
        }";
        $m["DASHBOARD_MAIN_GAUGE_POK_NADEJ"] = "function (ths, panel, val) {
            val = val || {};
            var data = [" . $dataForWsFunc['ws_func']['systemFill']['fill_meh_3'] . "];
            if (typeof val.data != 'undefined'){
                data = [val.data.fill_meh_3];
            }
            var chart_opt = {
                exporting: {
                    enabled: false,
                    url: 'http://127.0.0.1/lib/highcharts/modules/index.php'
                },
                chart: {
                    renderTo: 'gaugePokNadej',
                    type: 'solidgauge',
                    backgroundColor: 'transparent',
                    plotBackgroundColor: 'transparent',
                    borderColor: 'transparent',
                    animation: true
                },
                title: {
                    text: null
                },
                pane: {
                    center: ['50%', '85%'],
                    size: '100%',
                    startAngle: -90,
                    endAngle: 90,
                    background: {
                        backgroundColor: '#fff',
                        borderColor: 'transparent',
                        innerRadius: '60%',
                        outerRadius: '100%',
                        shape: 'arc'
                    }
                },
                tooltip: {
                    enabled: false
                },
                yAxis: {
                    stops: [
                        [0.35, '#F87263'],
                        [0.7, '#FFD148'],
                        [0.71, '#92CF50']
                    ],
                    lineWidth: 0,
                    labels: {
                        enabled: false
                    },
                    min: 0,
                    max: 100,
                    title: {
                        text: 'Показатели надежности',
                        style: {
                            color: '#8A8A8A',
                            fontFamily: 'PT Sans Regular, sans-serif',
                            fontSize: '12px !important',
                            textTransform: 'uppercase',
                            fontWeight: 'bold'
                        },
                        y: -50
                    },
                    minorTickColor: '#C8CED4',
                    minorTickLength: 4,
                    tickColor: '#C8CED4',
                    tickLength: 3,
                    minorTickPosition: 'inside',
                    tickPosition: 'inside',
                    tickAmount: 1,
                    crosshair: false,
                    offset: -27
                },
                xAxis: {
                    crosshair: false
                },
                colors: ['#4CB4FF'],
                plotOptions: {
                    solidgauge: {
                        dataLabels: {
                            y: 0,
                            borderWidth: 0,
                            useHTML: true,
                            style: {
                                color: '#484B45',
                                fontSize: '19px',
                                fontWeight: 'bold',
                                fontFamily: 'sans-serif'
                            }
                        }
                    }
                },
                series: [{
                    id: 1,
                    data: data,
                    dataLabels: {
                        format: '<div class=\"solid-gauge-datalable\">{y}</div>'
                    }
                }]
            };
            ws_globals['DASHBOARD_MAIN_GAUGE_POK_NADEJ'] = new Highcharts.Chart(chart_opt);
        }";
        $m["DASHBOARD_MAIN_GAUGE_TOIR"] = "function (ths, panel, val) {
            val = val || {};
            var data = [" . $dataForWsFunc['ws_func']['systemFill']['fill_meh_4'] . "];
            if (typeof val.data != 'undefined'){
                data = [val.data.fill_meh_4];
            }
            var chart_opt = {
                exporting: {
                    enabled: false,
                    url: 'http://127.0.0.1/lib/highcharts/modules/index.php'
                },
                chart: {
                    renderTo: 'gaugeToir',
                    type: 'solidgauge',
                    backgroundColor: 'transparent',
                    plotBackgroundColor: 'transparent',
                    borderColor: 'transparent',
                    animation: true
                },
                title: {
                    text: null
                },
                pane: {
                    center: ['50%', '85%'],
                    size: '100%',
                    startAngle: -90,
                    endAngle: 90,
                    background: {
                        backgroundColor: '#fff',
                        borderColor: 'transparent',
                        innerRadius: '60%',
                        outerRadius: '100%',
                        shape: 'arc'
                    }
                },
                tooltip: {
                    enabled: false
                },
                yAxis: {
                    stops: [
                        [0.35, '#F87263'],
                        [0.7, '#FFD148'],
                        [0.71, '#92CF50']
                    ],
                    lineWidth: 0,
                    labels: {
                        enabled: false
                    },
                    min: 0,
                    max: 100,
                    title: {
                        text: 'Графики ТОиР',
                        style: {
                            color: '#8A8A8A',
                            fontFamily: 'PT Sans Regular, sans-serif',
                            fontSize: '12px !important',
                            textTransform: 'uppercase',
                            fontWeight: 'bold'
                        },
                        y: -50
                    },
                    minorTickColor: '#C8CED4',
                    minorTickLength: 4,
                    tickColor: '#C8CED4',
                    tickLength: 3,
                    minorTickPosition: 'inside',
                    tickPosition: 'inside',
                    tickAmount: 1,
                    crosshair: false,
                    offset: -27
                },
                xAxis: {
                    crosshair: false
                },
                colors: ['#4CB4FF'],
                plotOptions: {
                    solidgauge: {
                        dataLabels: {
                            y: 0,
                            borderWidth: 0,
                            useHTML: true,
                            style: {
                                color: '#484B45',
                                fontSize: '19px',
                                fontWeight: 'bold',
                                fontFamily: 'sans-serif'
                            }
                        }
                    }
                },
                series: [{
                    id: 1,
                    data: data,
                    dataLabels: {
                        format: '<div class=\"solid-gauge-datalable\">{y}</div>'
                    }
                }]
            };
            ws_globals['DASHBOARD_MAIN_GAUGE_TOIR'] = new Highcharts.Chart(chart_opt);
        }";

        $m["DASHBOARD_MAIN_GAUGE_PLAN_TOIR"] = "function (ths, panel, val) {
            val = val || {};
            var data = [" . $dataForWsFunc['ws_func']['risk']['riskMeh'] . "];
            if (typeof val.data != 'undefined'){
                data = [val.data.riskMeh];
            }
            var chart_opt = {
                exporting: {
                    enabled: false,
                    url: 'http://127.0.0.1/lib/highcharts/modules/index.php'
                },
                chart: {
                    renderTo: 'dashboardGaugePlanToir',
                    type: 'solidgauge',
                    backgroundColor: 'transparent',
                    plotBackgroundColor: 'transparent'
                },
                title: {
                    text: null
                },
                pane: {
                    center: ['50%', '85%'],
                    size: '100%',
                    startAngle: -90,
                    endAngle: 90,
                    background: {
                        backgroundColor: '#fff',
                        innerRadius: '60%',
                        outerRadius: '100%',
                        shape: 'arc'
                    },
                    borderColor: 'transparent'
                },
                tooltip: {
                    enabled: false
                },
                yAxis: {
                    stops: [
                        [0.3, '#92CF50'],
                        [0.89, '#FFD148'],
                        [0.9, '#F87263']
                    ],
                    lineWidth: 0,
                    labels: {
                        enabled: false
                    },
                    min: 0,
                    max: 100,
                    title: {
                        text: 'Вероятность отказа',
                        style: {
                            color: '#8A8A8A',
                            fontFamily: 'PT Sans Regular, sans-serif',
                            fontSize: '12px !important',
                            textTransform: 'uppercase',
                            fontWeight: 'bold'
                        },
                        y: -83
                    },
                    minorTickColor: '#C8CED4',
                    minorTickLength: 4,
                    tickColor: '#C8CED4',
                    tickLength: 3,
                    minorTickPosition: 'inside',
                    tickPosition: 'inside',
                    tickAmount: 1,
                    crosshair: false,
                    offset: -35
                },
                xAxis: {
                    crosshair: false
                },
                colors: ['#4CB4FF'],
                plotOptions: {
                    solidgauge: {
                        dataLabels: {
                            y: 5,
                            borderWidth: 0,
                            useHTML: true,
                            style: {
                                color: '#484B45',
                                fontSize: '19px',
                                fontWeight: 'bold',
                                fontFamily: 'sans-serif'
                            }
                        },
                        shadow: {
                            enabled: true,
                            offsetY: '10px'
                        }
                    }
                },
                series: [{
                    id: 1,
                    data: data,
                    dataLabels: {
                        format: '<div class=\"solid-gauge-datalable\">{y}</div>'
                    }
                }]
            };
            ws_globals['DASHBOARD_MAIN_GAUGE_PLAN_TOIR'] = new Highcharts.Chart(chart_opt);
        }";
        // end dashboard main gauge
        // begin main carousel charts

        /* begin of disabled charts
        $m["DASHBOARD_LINE_PLAN_TOIR_MONTH"] = "function (ths, panel, val) {
            val = val || {};
            var data_plan = " . ws_jsonEncode($dataGauge['toir']['month_plan']) . ";
            var data_fact = " . ws_jsonEncode($dataGauge['toir']['month_fact']) . ";
            if (typeof val.data != 'undefined'){
                data_plan = val.data.month_plan;
                data_fact = val.data.month_fact;
            }
            function getMonth(v) {
                var m, mName, d, dateArr;
                if (v !== undefined) {
                    dateArr = v.split('.');
                    m = +dateArr[1];
                } else {
                    d = new Date();
                    m = d.getMonth() + 1;
                };
                switch (m) {
                    case 1:
                        mName = 'Январь';
                        break;
                    case 2:
                        mName = 'Февраль';
                        break;
                    case 3:
                        mName = 'Март';
                        break;
                    case 4:
                        mName = 'Апрель';
                        break;
                    case 5:
                        mName = 'Май';
                        break;
                    case 6:
                        mName = 'Июнь';
                        break;
                    case 7:
                        mName = 'Июль';
                        break;
                    case 8:
                        mName = 'Август';
                        break;
                    case 9:
                        mName = 'Сентябрь';
                        break;
                    case 10:
                        mName = 'Октябрь';
                        break;
                    case 11:
                        mName = 'Ноябрь';
                        break;
                    case 12:
                        mName = 'Декабрь';
                        break;
                };
                return mName;
            };
            var chart_opt = {
                exporting: {
                    enabled: false,
                    url: 'http://127.0.0.1/lib/highcharts/modules/index.php'
                },
                chart: {
                    renderTo: 'dashboardLinePlanToirMonth',
                    backgroundColor: 'transparent',
                    plotBackgroundColor: 'transparent',
                    marginTop: 50,
                    type: 'column'
                },
                title: {
                    text: 'За ' + getMonth(val.datetime),
                    style: {
                        color: '#8A8A8A',
                        fontFamily: 'PT Sans Regular, sans-serif',
                        fontSize: '12px !important',
                        textTransform: 'uppercase',
                        fontWeight: 'bold'
                    }
                },
                yAxis: [{
                    title: {
                        enabled: false
                    },
                    visible: false,
                    lineColor: 'transparent',
                    tickColor: 'transparent',
                    gridLineColor: 'transparent',
                    minorGridLineColor: '#f2f2f2',
                    crosshair: false,
                    labels: {
                        enabled: false
                    },
                    min: 0,
                    max: data_plan[0],
                    tickInterval: 0.01
                },{
                    title: {
                        enabled: false
                    },
                    visible: false,
                    lineColor: 'transparent',
                    tickColor: 'transparent',
                    gridLineColor: 'transparent',
                    minorGridLineColor: '#f2f2f2',
                    crosshair: false,
                    labels: {
                        enabled: false
                    },
                    min: 0,
                    max: data_plan[1],
                    tickInterval: 0.01
                },{
                    title: {
                        enabled: false
                    },
                    visible: false,
                    lineColor: 'transparent',
                    tickColor: 'transparent',
                    gridLineColor: 'transparent',
                    minorGridLineColor: '#f2f2f2',
                    crosshair: false,
                    labels: {
                        enabled: false
                    },
                    min: 0,
                    max: data_plan[2],
                    tickInterval: 0.01
                },{
                    title: {
                        enabled: false
                    },
                    visible: false,
                    lineColor: 'transparent',
                    tickColor: 'transparent',
                    gridLineColor: 'transparent',
                    minorGridLineColor: '#f2f2f2',
                    crosshair: false,
                    labels: {
                        enabled: false
                    },
                    min: 0,
                    max: data_plan[3],
                    tickInterval: 0.01
                }],
                xAxis: {
                    categories: ['Ремонты', 'ТО', 'Ревизии и<br/> <b>тарировки</b>', 'Тех. осв.<br/><b> и ЭПБ</b>'],
                    title: {
                        enabled: false
                    },
                    lineColor: '#dcdcdc',
                    tickColor: '#dcdcdc',
                    minorGridLineColor: '#dcdcdc',
                    crosshair: false,
                    labels: {
                        enabled: true,
                        style: {
                            fontSize: '10px !important',
                            color: '#8a8a8a',
                            fontWeight: 'bold !important'
                        }
                    }
                },
                legend: {
                    enabled: false
                },
                plotOptions: {
                    series: {
                        dataLabels: {
                            enabled: false
                        },
                        minPointLength: 5,
                        borderRadius: 3,
                        borderWidth: 0,
                        pointWidth: 20,
                    }
                },
                tooltip: {
                    formatter: function () {
                        var s = '<b>' + this.x + '</b>';

                        $.each(this.points, function () {
                            s += '<br/>' + this.series.name + ': ' + this.y + ' ед.';
                        });

                        return s;
                    },
                    shared: true
                },
                colors: ['#92cf50', '#73c7e2'],
                series: [{
                    index: 0,
                    name: 'План',
                    data: [[0,data_plan[0]]],
                    type: 'column',
                    pointPlacement: 0.1
                },{
                    index: 0,
                    name: 'Факт',
                    data: [[0,data_fact[0]]],
                    type: 'column',
                    pointPlacement: 0.35
                },{
                    index: 1,
                    name: 'План',
                    data: [[1,data_plan[1]]],
                    type: 'column',
                    yAxis: 1,
                    pointPlacement: -0.05
                },{
                    index: 1,
                    name: 'Факт',
                    data: [[1,data_fact[1]]],
                    type: 'column',
                    yAxis: 1,
                    pointPlacement: 0.2
                },{
                    index: 2,
                    name: 'План',
                    data: [[2,data_plan[2]]],
                    type: 'column',
                    yAxis: 2,
                    pointPlacement: -0.2
                },{
                    index: 2,
                    name: 'Факт',
                    data: [[2,data_fact[2]]],
                    type: 'column',
                    yAxis: 2,
                    pointPlacement: 0.05
                },{
                    index: 3,
                    name: 'План',
                    data: [[3,data_plan[3]]],
                    type: 'column',
                    yAxis: 3,
                    pointPlacement: -0.35
                },{
                    index: 3,
                    name: 'Факт',
                    data: [[3,data_fact[3]]],
                    type: 'column',
                    yAxis: 3,
                    pointPlacement: -0.1
                }]
            };
            ws_globals['DASHBOARD_LINE_PLAN_TOIR_MONTH'] = new Highcharts.Chart(chart_opt);
        }";

        $m["DASHBOARD_LINE_PLAN_TOIR_YEAR"] = "function (ths, panel, val) {
            val = val || {};
            var data_plan = " . ws_jsonEncode($dataGauge['toir']['year_plan']) . ";
            var data_fact = " . ws_jsonEncode($dataGauge['toir']['year_fact']) . ";
            if (typeof val.data != 'undefined'){
                data_plan = val.data.year_plan;
                data_fact = val.data.year_fact;
            }
            val = val || {};
            function getYear(v) {
                var d, y, dateArr;
                if (v !== undefined) {
                    dateArr = v.split('.');
                    y = +dateArr[2];
                } else {
                    d = new Date();
                    y = d.getFullYear();
                };
                return y;
            };
            function getMaxOfData(numArray) {
                return Math.min.apply(null, numArray);
            };
            var chart_opt = {
                exporting: {
                    enabled: false,
                    url: 'http://127.0.0.1/lib/highcharts/modules/index.php'
                },
                chart: {
                    renderTo: 'dashboardLinePlanToirYear',
                    backgroundColor: 'transparent',
                    plotBackgroundColor: 'transparent',
                    marginTop: 50,
                    type: 'column'
                },
                title: {
                    text: 'За ' + getYear(val.datetime) + ' год',
                    style: {
                        color: '#8A8A8A',
                        fontFamily: 'PT Sans Regular, sans-serif',
                        fontSize: '12px !important',
                        textTransform: 'uppercase',
                        fontWeight: 'bold'
                    }
                },
                yAxis: [{
                    title: {
                        enabled: false
                    },
                    visible: false,
                    lineColor: 'transparent',
                    tickColor: 'transparent',
                    gridLineColor: 'transparent',
                    minorGridLineColor: '#f2f2f2',
                    crosshair: false,
                    labels: {
                        enabled: false
                    },
                    min: 0,
                    max: data_plan[0],
                    tickInterval: 0.01
                },{
                    title: {
                        enabled: false
                    },
                    visible: false,
                    lineColor: 'transparent',
                    tickColor: 'transparent',
                    gridLineColor: 'transparent',
                    minorGridLineColor: '#f2f2f2',
                    crosshair: false,
                    labels: {
                        enabled: false
                    },
                    min: 0,
                    max: data_plan[1],
                    tickInterval: 0.01
                },{
                    title: {
                        enabled: false
                    },
                    visible: false,
                    lineColor: 'transparent',
                    tickColor: 'transparent',
                    gridLineColor: 'transparent',
                    minorGridLineColor: '#f2f2f2',
                    crosshair: false,
                    labels: {
                        enabled: false
                    },
                    min: 0,
                    max: data_plan[2],
                    tickInterval: 0.01
                },{
                    title: {
                        enabled: false
                    },
                    visible: false,
                    lineColor: 'transparent',
                    tickColor: 'transparent',
                    gridLineColor: 'transparent',
                    minorGridLineColor: '#f2f2f2',
                    crosshair: false,
                    labels: {
                        enabled: false
                    },
                    min: 0,
                    max: data_plan[3],
                    tickInterval: 0.01
                }],
                xAxis: {
                    categories: ['Ремонты', 'ТО', 'Ревизии и<br/> <b>тарировки</b>', 'Тех. осв.<br/><b> и ЭПБ</b>'],
                    title: {
                        enabled: false
                    },
                    lineColor: '#dcdcdc',
                    tickColor: '#dcdcdc',
                    minorGridLineColor: '#dcdcdc',
                    crosshair: false,
                    labels: {
                        enabled: true,
                        style: {
                            fontSize: '10px !important',
                            color: '#8a8a8a',
                            fontWeight: 'bold !important'
                        }
                    }
                },
                legend: {
                    enabled: false
                },
                plotOptions: {
                    series: {
                        dataLabels: {
                            enabled: false
                        },
                        minPointLength: 5,
                        borderRadius: 3,
                        borderWidth: 0,
                        pointWidth: 20,
                    }
                },
                tooltip: {
                    formatter: function () {
                        var s = '<b>' + this.x + '</b>';

                        $.each(this.points, function () {
                            s += '<br/>' + this.series.name + ': ' + this.y + ' ед.';
                        });

                        return s;
                    },
                    shared: true
                },
                colors: ['#92cf50', '#73c7e2'],
                series: [{
                    index: 0,
                    name: 'План',
                    data: [[0,data_plan[0]]],
                    type: 'column',
                    pointPlacement: 0.1
                },{
                    index: 0,
                    name: 'Факт',
                    data: [[0,data_fact[0]]],
                    type: 'column',
                    pointPlacement: 0.35
                },{
                    index: 1,
                    name: 'План',
                    data: [[1,data_plan[1]]],
                    type: 'column',
                    yAxis: 1,
                    pointPlacement: -0.05
                },{
                    index: 1,
                    name: 'Факт',
                    data: [[1,data_fact[1]]],
                    type: 'column',
                    yAxis: 1,
                    pointPlacement: 0.2
                },{
                    index: 2,
                    name: 'План',
                    data: [[2,data_plan[2]]],
                    type: 'column',
                    yAxis: 2,
                    pointPlacement: -0.2
                },{
                    index: 2,
                    name: 'Факт',
                    data: [[2,data_fact[2]]],
                    type: 'column',
                    yAxis: 2,
                    pointPlacement: 0.05
                },{
                    index: 3,
                    name: 'План',
                    data: [[3,data_plan[3]]],
                    type: 'column',
                    yAxis: 3,
                    pointPlacement: -0.35
                },{
                    index: 3,
                    name: 'Факт',
                    data: [[3,data_fact[3]]],
                    type: 'column',
                    yAxis: 3,
                    pointPlacement: -0.1
                }]
            };
            ws_globals['DASHBOARD_LINE_PLAN_TOIR_YEAR'] = new Highcharts.Chart(chart_opt);
        }";

        $m["DASHBOARD_ECONOMICS_TABLE"] = "function (ths, panel, val) {
            var data_fact = [61802075.50, 23582267.80, 5111355.93, 1993780.00, 81801043.79];
            var data_plan = [61280717.43, 21294052.00, 4365834.00, 550000.00, 29137389.31];

            var chart_opt = {
                exporting: {
                    enabled: false,
                    url: 'http://127.0.0.1/lib/highcharts/modules/index.php'
                },
                chart: {
                    renderTo: 'mainEconomicsChart',
                    backgroundColor: 'transparent',
                    plotBackgroundColor: null,
                    plotBorderWidth: 0,
                    plotShadow: false,
                    marignTop: 100,
                    type: 'column'
                },
                title: {
                    text: 'Блок экономики',
                    style: {
                        color: '#8A8A8A',
                        fontFamily: 'PT Sans Regular, sans-serif',
                        fontSize: '12px !important',
                        textTransform: 'uppercase',
                        fontWeight: 'bold'
                    }
                },
                xAxis: {
                    categories: ['ТО', 'ТР', 'КР', 'Диагностика', 'ОНВСС'],
                    title: {
                        text: null
                    },
                    opposite: true
                },
                yAxis: [{
                    id: 0,
                    title: {
                        enabled: false
                    },
                    visible: false,
                    lineColor: 'transparent',
                    tickColor: 'transparent',
                    gridLineColor: 'transparent',
                    minorGridLineColor: '#f2f2f2',
                    crosshair: false,
                    labels: {
                        enabled: false
                    },
                    min: 0,
                    max: data_plan[0],
                    tickInterval: 0.01
                },{
                    id: 1,
                    title: {
                        enabled: false
                    },
                    visible: false,
                    lineColor: 'transparent',
                    tickColor: 'transparent',
                    gridLineColor: 'transparent',
                    minorGridLineColor: '#f2f2f2',
                    crosshair: false,
                    labels: {
                        enabled: false
                    },
                    min: 0,
                    max: data_plan[1],
                    tickInterval: 0.01
                },{
                    id: 2,
                    title: {
                        enabled: false
                    },
                    visible: false,
                    lineColor: 'transparent',
                    tickColor: 'transparent',
                    gridLineColor: 'transparent',
                    minorGridLineColor: '#f2f2f2',
                    crosshair: false,
                    labels: {
                        enabled: false
                    },
                    min: 0,
                    max: data_plan[2],
                    tickInterval: 0.01
                },{
                    id: 3,
                    title: {
                        enabled: false
                    },
                    visible: false,
                    lineColor: 'transparent',
                    tickColor: 'transparent',
                    gridLineColor: 'transparent',
                    minorGridLineColor: '#f2f2f2',
                    crosshair: false,
                    labels: {
                        enabled: false
                    },
                    min: 0,
                    max: data_plan[3],
                    tickInterval: 0.01
                },{
                    id: 4,
                    title: {
                        enabled: false
                    },
                    visible: false,
                    lineColor: 'transparent',
                    tickColor: 'transparent',
                    gridLineColor: 'transparent',
                    minorGridLineColor: '#f2f2f2',
                    crosshair: false,
                    labels: {
                        enabled: false
                    },
                    min: 0,
                    max: data_plan[4],
                    tickInterval: 0.01
                }],
                tooltip: {
                    formatter: function () {
                        var s = '<b>' + this.x + '</b>';

                        $.each(this.points, function () {
                            s += '<br/>' + this.series.name + ': ' + this.y + ' руб.';
                        });

                        return s;
                    },
                    shared: true
                },
                legend: {
                    enabled: false
                },
                plotOptions: {
                    series: {
                        dataLabels: {
                            enabled: false
                        },
                        minPointLength: 5,
                        borderRadius: 3,
                        borderWidth: 0,
                        pointWidth: 3
                    }
                },
                colors: ['#92cf50', '#73c7e2'],
                series: [{
                    index: 0,
                    name: 'План',
                    type: 'column',
                    data: [[0,data_plan[0]]]
                }, {
                    index: 0,
                    name: 'Факт',
                    type: 'column',
                    data: [[0,data_fact[0]]]
                }, {
                    index: 1,
                    name: 'План',
                    type: 'column',
                    data: [[1,data_plan[1]]]
                }, {
                    index: 1,
                    name: 'Факт',
                    type: 'column',
                    data: [[1,data_fact[1]]]
                }, {
                    index: 2,
                    name: 'План',
                    type: 'column',
                    data: [[2,data_plan[2]]]
                }, {
                    index: 2,
                    name: 'Факт',
                    type: 'column',
                    data: [[2,data_fact[2]]]
                }, {
                    index: 3,
                    name: 'План',
                    type: 'column',
                    data: [[3,data_plan[3]]]
                }, {
                    index: 3,
                    name: 'Факт',
                    type: 'column',
                    data: [[3,data_fact[3]]]
                }, {
                    index: 4,
                    name: 'План',
                    type: 'column',
                    data: [[4,data_plan[4]]]
                }, {
                    index: 4,
                    name: 'Факт',
                    type: 'column',
                    data: [[4,data_fact[4]]]
                }]
            };
            ws_globals['PIE_BIG_PIPELINE'] = new Highcharts.Chart(chart_opt);
        }";

        end of disabled charts */
        $m["DASHBOARD_MAIN_LINE_ALL"] = "function (ths, panel, val) {
            val = val || {};
            var categories = " . str_replace("\"", "'", ws_jsonEncode($dataForWsFunc['ws_func']['safetyDyn']['categories'])) . ";
            var data_zn = " . str_replace("\"", "'", $dataForWsFunc['ws_func']['safetyDyn']['safetyDynAll']['safety_zn']) . ";
            var data_rvp = " . str_replace("\"", "'", $dataForWsFunc['ws_func']['safetyDyn']['safetyDynAll']['safety_rvp']) . ";
            var data_znds = " . str_replace("\"", "'", $dataForWsFunc['ws_func']['safetyDyn']['safetyDynAll']['safety_znds']) . ";
            var data_zndh = " . str_replace("\"", "'", $dataForWsFunc['ws_func']['safetyDyn']['safetyDynAll']['safety_zndh']) . ";
            if (typeof val.data != 'undefined'){
                categories = val.data.categories;
                data_zn = jQuery.parseJSON(val.data.safetyDynAll.safety_zn);
                data_rvp = jQuery.parseJSON(val.data.safetyDynAll.safety_rvp);
                data_znds = jQuery.parseJSON(val.data.safetyDynAll.safety_znds);
                data_zndh = jQuery.parseJSON(val.data.safetyDynAll.safety_zndh);
            }
            
            var chart_opt = {
                exporting: {
                    enabled: false,
                    url: 'http://127.0.0.1/lib/highcharts/modules/index.php'
                },
                chart: {
                    renderTo: 'mainChart-all',
                    type: 'line',
                    backgroundColor: 'transparent',
                    plotBackgroundColor: 'transparent',
                    plotBorderColor: '#DDE2E4',
                    marginTop: 20,
                    zoomType: false
                },
                title: {
                    text: null
                },
                xAxis: {
                    categories: categories,
                    minorGridLineColor: 'transparent',
                    alternateGridColor: '#FFFFFF',
                    gridLineColor: 'transparent',
                    gridLineColor: 'linear-gradient(0deg, red, blue)',
                    gridLineWidth: 0,
                    tickColor: 'transparent',
                    lineColor: '#D6DADF',
                    title: {
                        enabled: false
                    },
                    labels: {
                        style: {
                            color: '#464A57',
                            fontSize: '10px'
                        },
                        step: 2
                    },
                    crosshair: {
                        width: 70
                    }
                },
                yAxis: {
                    gridLineDashStyle: 'longdash',
                    minorGridLineColor: 'transparent',
                    tickColor: 'transparent',
                    lineWidth: 6,
                    step: 10,
                    lineColor: 'transparent',
                    title: {
                        enabled: false
                    },
                    min: null,
                    step: 10,
                    max: 100,
                    tickAmount: 11,
                    crosshair: false,
                    labels: {
                        style: {
                            color: '#9DA1B0',
                            fontSize: '11px'
                        }
                    }
                },
                plotOptions: {
                    series: {
                        lineWidth: 3,
                        dataLabels: {
                            enabled: false
                        },
                        marker: {
                            symbol: 'round',
                            enabled: false,
                            height: 14,
                            width: 14,
                        },
                        shadow: {
                            width: 6,
                            offsetY: 3,
                            opacity: 0.30548
                        },
                        states: {
                            hover: {
                                halo: {
                                    size: 8,
                                    opacity: 1
                                }
                            }
                        }
                    }
                },
                legend: {
                    align: 'left',
                    verticalAlign: 'bottom',
                    layout: 'horizontal',
                    enabled: true,
                    y: 0,
                    x: 0
                },
                tooltip: {
                    borderColor: 'silver',
                    backgroundColor: 'rgba(255, 255, 255, 0.9)',
                    formatter: function() {
                        var s = [];
                        s.push('<span style=\"font-size: 0.8rem;\">' + this.x + '</span>');
                        this.points.forEach(function(point) {
                        s.push('<br><span style=\"display: inline-block; margin-bottom: 30px;\">' + point.series.name + ' — <b>' + point.y + '%</b></span>');
                        });
                        return s;
                    },
                    style: {
                        fontSize: '1.1rem',
                        fontFamily: 'Arial, sans-serif',
                    },
                    shared: true
                },
                defs: {
                    glow: {
                        tagName: 'filter',
                        id: 'glow',
                        opacity: 0.5,
                        children: [{
                            tagName: 'feGaussianBlur',
                            result: 'coloredBlur',
                            stdDeviation: 2.5
                        }, {
                            tagName: 'feMerge',
                            children: [{
                                tagName: 'feMergeNode',
                                in: 'coloredBlur'
                            }, {
                                tagName: 'feMergeNode',
                                in: 'SourceGraphic'
                            }]
                        }]
                    }
                },
                series: [{
                    id: 1,
                    name: 'Показатель надежности ЗН',
                    color: '#EE7669',
                    shadow: '#EE7669',
                    data: data_zn,
                }, {
                    id: 2,
                    name: 'Показатель надежности РВП',
                    color: '#73C7E2',
                    shadow: '#73C7E2',
                    data: data_rvp,
                }, {
                    id: 3,
                    name: 'Показатель надежности ЗНДС',
                    color: '#84DC74',
                    shadow: '#84DC74',
                    data: data_znds,
                }, {
                    id: 4,
                    name: 'Показатель надежности ЗНДХ',
                    color: '#FFBC42',
                    shadow: '#FFBC42',
                    data: data_zndh,
                }, ]
            };
            ws_globals['DASHBOARD_MAIN_LINE_ALL'] = new Highcharts.Chart(chart_opt);
        }";
        $m["ECONOMICSCHART_TO"] = "function (ths, panel, val) {
            var data_plan = 61802075.50;
            var data_fact = 61280717.43;

            chart_opt = {
                exporting: {
                    enabled: false,
                    url: 'http://127.0.0.1/lib/highcharts/modules/index.php'
                },
                chart: {
                    renderTo: 'economicsChart_TO',
                    type: 'bar',
                    backgroundColor: 'transparent',
                    plotBackgroundColor: 'transparent',
                    plotBorderColor: 'transparent',
                    zoomType: false,
                    height: 90
                },
                title: {
                    text: 'Технический осмотр, руб.',
                    align: 'left',
                    margin: 5,
                    style: {
                        color: '#8A8A8A',
                        fontFamily: 'PT Sans Regular, sans-serif',
                        fontSize: '12px !important',
                        fontWeight: 'bold'
                    },
                    x: 5
                },
                legend: {
                    enabled: false
                },
                yAxis: [{
                    title: {
                        enabled: false
                    },
                    visible: false,
                    lineColor: 'transparent',
                    tickColor: 'transparent',
                    gridLineColor: 'transparent',
                    minorGridLineColor: 'transparent',
                    minorTickColor: 'transparent',
                    crosshair: false,
                    labels: {
                        enabled: false
                    },
                    min: 0,
                    max: data_plan
                }],
                xAxis: {
                    title: {
                        enabled: false
                    },
                    categories: ['Технический осмотр, руб.'],
                    labels: {
                        enabled: false
                    },
                    tickLength: 0,
                    lineColor: 'transparent',
                    tickColor: 'transparent',
                    gridLineColor: 'transparent',
                    minorTickColor: 'transparent',
                    minorGridLineColor: 'transparent'
                },
                tooltip: {
                    formatter: function () {
                        var s = '<b>' + this.x + '</b>';

                        $.each(this.points, function () {
                            s += '<br/>' + this.series.name + ': ' + Highcharts.numberFormat(this.y,2,',',' ') + ' руб.';
                        });

                        return s;
                    },
                    shared: true
                },
                colors: ['#92cf50', '#73c7e2'],
                plotOptions: {
                    series: {
                        dataLabels: {
                            align: 'left',
                            enabled: true,
                            inside: true,
                            color: '#fff',
                            style: {
                                textOutline: null
                            },
                            format: '{point.y:,.2f}'
                        },
                        pointWidth: 20,
                        groupPadding: 0,
                        borderRadius: 3,
                        minPointLength: 100
                    }
                },
                series: [{
                    name: 'План',
                    data: [data_plan]
                }, {
                    name: 'Факт',
                    data: [data_fact]
                }]
            };

            ws_globals['ECONOMICSCHART_TO'] = new Highcharts.setOptions({
                lang: {
                    decimalPoint: ',',
                    thousandsSep: ' '
                }
            });
            ws_globals['ECONOMICSCHART_TO'] = new Highcharts.Chart(chart_opt);
        }";
        $m["ECONOMICSCHART_TR"] = "function (ths, panel, val) {
            var data_plan = 23582267.80;
            var data_fact = 21294052.00;

            chart_opt = {
                exporting: {
                    enabled: false,
                    url: 'http://127.0.0.1/lib/highcharts/modules/index.php'
                },
                chart: {
                    renderTo: 'economicsChart_TR',
                    type: 'bar',
                    backgroundColor: 'transparent',
                    plotBackgroundColor: 'transparent',
                    plotBorderColor: 'transparent',
                    zoomType: false,
                    height: 90
                },
                title: {
                    text: 'Технический ремонт, руб.',
                    align: 'left',
                    margin: 5,
                    style: {
                        color: '#8A8A8A',
                        fontFamily: 'PT Sans Regular, sans-serif',
                        fontSize: '12px !important',
                        fontWeight: 'bold'
                    },
                    x: 5
                },
                legend: {
                    enabled: false
                },
                yAxis: [{
                    title: {
                        enabled: false
                    },
                    visible: false,
                    lineColor: 'transparent',
                    tickColor: 'transparent',
                    gridLineColor: 'transparent',
                    minorGridLineColor: 'transparent',
                    minorTickColor: 'transparent',
                    crosshair: false,
                    labels: {
                        enabled: false
                    },
                    min: 0,
                    max: data_plan
                }],
                xAxis: {
                    title: {
                        enabled: false
                    },
                    categories: ['Технический ремонт, руб.'],
                    labels: {
                        enabled: false
                    },
                    tickLength: 0,
                    lineColor: 'transparent',
                    tickColor: 'transparent',
                    gridLineColor: 'transparent',
                    minorTickColor: 'transparent',
                    minorGridLineColor: 'transparent'
                },
                tooltip: {
                    formatter: function () {
                        var s = '<b>' + this.x + '</b>';

                        $.each(this.points, function () {
                            s += '<br/>' + this.series.name + ': ' + Highcharts.numberFormat(this.y,2,',',' ') + ' руб.';
                        });

                        return s;
                    },
                    shared: true
                },
                colors: ['#92cf50', '#73c7e2'],
                plotOptions: {
                    series: {
                        dataLabels: {
                            align: 'left',
                            enabled: true,
                            inside: true,
                            color: '#fff',
                            style: {
                                textOutline: null
                            },
                            format: '{point.y:,.2f}'
                        },
                        pointWidth: 20,
                        groupPadding: 0,
                        borderRadius: 3,
                        minPointLength: 90
                    }
                },
                series: [{
                    name: 'План',
                    data: [data_plan]
                }, {
                    name: 'Факт',
                    data: [data_fact]
                }]
            };

            ws_globals['ECONOMICSCHART_TR'] = new Highcharts.setOptions({
                lang: {
                    decimalPoint: ',',
                    thousandsSep: ' '
                }
            });
            ws_globals['ECONOMICSCHART_TR'] = new Highcharts.Chart(chart_opt);
        }";
        $m["ECONOMICSCHART_KP"] = "function (ths, panel, val) {
            var data_plan = 5111355.93;
            var data_fact = 4365834.00;

            chart_opt = {
                exporting: {
                    enabled: false,
                    url: 'http://127.0.0.1/lib/highcharts/modules/index.php'
                },
                chart: {
                    renderTo: 'economicsChart_KP',
                    type: 'bar',
                    backgroundColor: 'transparent',
                    plotBackgroundColor: 'transparent',
                    plotBorderColor: 'transparent',
                    zoomType: false,
                    height: 90
                },
                title: {
                    text: 'Капитальный ремонт, руб.',
                    align: 'left',
                    margin: 5,
                    style: {
                        color: '#8A8A8A',
                        fontFamily: 'PT Sans Regular, sans-serif',
                        fontSize: '12px !important',
                        fontWeight: 'bold'
                    },
                    x: 5
                },
                legend: {
                    enabled: false
                },
                yAxis: [{
                    title: {
                        enabled: false
                    },
                    visible: false,
                    lineColor: 'transparent',
                    tickColor: 'transparent',
                    gridLineColor: 'transparent',
                    minorGridLineColor: 'transparent',
                    minorTickColor: 'transparent',
                    crosshair: false,
                    labels: {
                        enabled: false
                    },
                    min: 0,
                    max: data_plan
                }],
                xAxis: {
                    title: {
                        enabled: false
                    },
                    categories: ['Капитальный ремонт, руб.'],
                    labels: {
                        enabled: false
                    },
                    tickLength: 0,
                    lineColor: 'transparent',
                    tickColor: 'transparent',
                    gridLineColor: 'transparent',
                    minorTickColor: 'transparent',
                    minorGridLineColor: 'transparent'
                },
                tooltip: {
                    formatter: function () {
                        var s = '<b>' + this.x + '</b>';

                        $.each(this.points, function () {
                            s += '<br/>' + this.series.name + ': ' + Highcharts.numberFormat(this.y,2,',',' ') + ' руб.';
                        });

                        return s;
                    },
                    shared: true
                },
                colors: ['#92cf50', '#73c7e2'],
                plotOptions: {
                    series: {
                        dataLabels: {
                            align: 'left',
                            enabled: true,
                            inside: true,
                            color: '#fff',
                            style: {
                                textOutline: null
                            },
                            format: '{point.y:,.2f}'
                        },
                        pointWidth: 20,
                        groupPadding: 0,
                        borderRadius: 3,
                        minPointLength: 90
                    }
                },
                series: [{
                    name: 'План',
                    data: [data_plan]
                }, {
                    name: 'Факт',
                    data: [data_fact]
                }]
            };

            ws_globals['ECONOMICSCHART_KP'] = new Highcharts.setOptions({
                lang: {
                    decimalPoint: ',',
                    thousandsSep: ' '
                }
            });
            ws_globals['ECONOMICSCHART_KP'] = new Highcharts.Chart(chart_opt);
        }";
        $m["ECONOMICSCHART_DIAG"] = "function (ths, panel, val) {
            var data_plan = 1993780.00;
            var data_fact = 550000.00;

            chart_opt = {
                exporting: {
                    enabled: false,
                    url: 'http://127.0.0.1/lib/highcharts/modules/index.php'
                },
                chart: {
                    renderTo: 'economicsChart_DIAG',
                    type: 'bar',
                    backgroundColor: 'transparent',
                    plotBackgroundColor: 'transparent',
                    plotBorderColor: 'transparent',
                    zoomType: false,
                    height: 90
                },
                title: {
                    text: 'Диагностика, руб.',
                    align: 'left',
                    margin: 5,
                    style: {
                        color: '#8A8A8A',
                        fontFamily: 'PT Sans Regular, sans-serif',
                        fontSize: '12px !important',
                        fontWeight: 'bold'
                    },
                    x: 5
                },
                legend: {
                    enabled: false
                },
                yAxis: [{
                    title: {
                        enabled: false
                    },
                    visible: false,
                    lineColor: 'transparent',
                    tickColor: 'transparent',
                    gridLineColor: 'transparent',
                    minorGridLineColor: 'transparent',
                    minorTickColor: 'transparent',
                    crosshair: false,
                    labels: {
                        enabled: false
                    },
                    min: 0,
                    max: data_plan
                }],
                xAxis: {
                    title: {
                        enabled: false
                    },
                    categories: ['Диагностика, руб.'],
                    labels: {
                        enabled: false
                    },
                    tickLength: 0,
                    lineColor: 'transparent',
                    tickColor: 'transparent',
                    gridLineColor: 'transparent',
                    minorTickColor: 'transparent',
                    minorGridLineColor: 'transparent'
                },
                tooltip: {
                    formatter: function () {
                        var s = '<b>' + this.x + '</b>';

                        $.each(this.points, function () {
                            s += '<br/>' + this.series.name + ': ' + Highcharts.numberFormat(this.y,2,',',' ') + ' руб.';
                        });

                        return s;
                    },
                    shared: true
                },
                colors: ['#92cf50', '#73c7e2'],
                plotOptions: {
                    series: {
                        dataLabels: {
                            align: 'left',
                            enabled: true,
                            inside: true,
                            color: '#fff',
                            style: {
                                textOutline: null
                            },
                            format: '{point.y:,.2f}'
                        },
                        pointWidth: 20,
                        groupPadding: 0,
                        borderRadius: 3,
                        minPointLength: 90
                    }
                },
                series: [{
                    name: 'План',
                    data: [data_plan]
                }, {
                    name: 'Факт',
                    data: [data_fact]
                }]
            };

            ws_globals['ECONOMICSCHART_DIAG'] = new Highcharts.setOptions({
                lang: {
                    decimalPoint: ',',
                    thousandsSep: ' '
                }
            });
            ws_globals['ECONOMICSCHART_DIAG'] = new Highcharts.Chart(chart_opt);
        }";
        $m["ECONOMICSCHART_ONVSS"] = "function (ths, panel, val) {
            var data_plan = 81801043.79;
            var data_fact = 29137389.31;

            chart_opt = {
                exporting: {
                    enabled: false,
                    url: 'http://127.0.0.1/lib/highcharts/modules/index.php'
                },
                chart: {
                    renderTo: 'economicsChart_ONVSS',
                    type: 'bar',
                    backgroundColor: 'transparent',
                    plotBackgroundColor: 'transparent',
                    plotBorderColor: 'transparent',
                    zoomType: false,
                    height: 90
                },
                title: {
                    text: 'ОНВСС, руб.',
                    align: 'left',
                    margin: 5,
                    style: {
                        color: '#8A8A8A',
                        fontFamily: 'PT Sans Regular, sans-serif',
                        fontSize: '12px !important',
                        fontWeight: 'bold'
                    },
                    x: 5
                },
                legend: {
                    enabled: false
                },
                yAxis: [{
                    title: {
                        enabled: false
                    },
                    visible: false,
                    lineColor: 'transparent',
                    tickColor: 'transparent',
                    gridLineColor: 'transparent',
                    minorGridLineColor: 'transparent',
                    minorTickColor: 'transparent',
                    crosshair: false,
                    labels: {
                        enabled: false
                    },
                    min: 0,
                    max: data_plan
                }],
                xAxis: {
                    title: {
                        enabled: false
                    },
                    categories: ['ОНВСС, руб.'],
                    labels: {
                        enabled: false
                    },
                    tickLength: 0,
                    lineColor: 'transparent',
                    tickColor: 'transparent',
                    gridLineColor: 'transparent',
                    minorTickColor: 'transparent',
                    minorGridLineColor: 'transparent'
                },
                tooltip: {
                    formatter: function () {
                        var s = '<b>' + this.x + '</b>';

                        $.each(this.points, function () {
                            s += '<br/>' + this.series.name + ': ' + Highcharts.numberFormat(this.y,2,',',' ') + ' руб.';
                        });

                        return s;
                    },
                    shared: true
                },
                colors: ['#92cf50', '#73c7e2'],
                plotOptions: {
                    series: {
                        dataLabels: {
                            align: 'left',
                            enabled: true,
                            inside: true,
                            color: '#fff',
                            style: {
                                textOutline: null
                            },
                            format: '{point.y:,.2f}'
                        },
                        pointWidth: 20,
                        groupPadding: 0,
                        borderRadius: 3,
                        minPointLength: 90
                    }
                },
                series: [{
                    name: 'План',
                    data: [data_plan]
                }, {
                    name: 'Факт',
                    data: [data_fact]
                }]
            };

            ws_globals['ECONOMICSCHART_ONVSS'] = new Highcharts.setOptions({
                lang: {
                    decimalPoint: ',',
                    thousandsSep: ' '
                }
            });
            ws_globals['ECONOMICSCHART_ONVSS'] = new Highcharts.Chart(chart_opt);
        }";
        // end main carousel charts
        $m["PIE_BIG_EQUIPMENT"] = "function (ths, panel, val) {
            val = val || {};
            var data = " . str_replace("\"", "'", $dataForWsFunc['ws_func']['stateEquipMeh']['stateDyn']) . ";
            var text = '" . $dataForWsFunc['ws_func']['stateEquipMeh']['stateDyn_point'] . " <span style=\"display: inline-block; color: #484B45; font-size: 12px;\"> " . $dataForWsFunc['ws_func']['stateEquipMeh']['stateDyn_max'] . "</span>';
            if (typeof val.data != 'undefined'){
                data = jQuery.parseJSON(val.data.stateDyn);
                text = val.data.stateDyn_point+'<span style=\"display: inline-block; margin-top: 5px; color: #484B45; font-size: 12px;\"> '+val.data.stateDyn_max+'</span>';
            }
            var chart_opt = {
                exporting: {
                    enabled: false,
                    url: 'http://127.0.0.1/lib/highcharts/modules/index.php'
                },
                chart: {
                    renderTo: 'pieBigEquipment',
                    backgroundColor: 'transparent',
                    plotBackgroundColor: null,
                    plotBorderWidth: 0,
                    plotShadow: false
                },
                title: {
                    text: 'Основное оборудование',
                    style: {
                        color: '#8A8A8A',
                        fontFamily: 'PT Sans Regular, sans-serif',
                        fontSize: '12px !important',
                        textTransform: 'uppercase',
                        fontWeight: 'bold'
                    },
                    y: 20
                },
                subtitle: {
                    text: text,
                    align: 'center',
                    verticalAlign: 'middle',
                    style: {
                        color: '#484B45',
                        fontWeight: 'bold',
                        fontSize: '16px'
                    },
                    y: 12
                },
                tooltip: {
                    enabled: true,
                    formatter: function() {
                        return this.point.name + ' — <b>' + this.y + '%</b>';
                    }
                },
                legend: {
                    enabled: true,
                    layout: 'horizontal',
                    verticalAlign: 'bottom',
                    align: 'center',
                    labelFormatter: function () {
                        return this.y + '%';
                    },
                    x: 0,
                    y: 20
                },
                plotOptions: {
                    pie: {
                        dataLabels: {
                            enabled: false,
                            distance: -50,
                            style: {
                                fontWeight: 'bold',
                                color: 'white'
                            }
                        },
                        startAngle: 0,
                        endAngle: 360,
                        center: ['50%', '50%'],
                        showInLegend: true
                    },
                    series: {
                        states: {
                            hover: {
                                enabled: true
                            }
                        },
                        point: {
                            events: {
                                legendItemClick: function () {
                                    return false;
                                }
                            }
                        }
                    }
                },
                colors: ['#92CF50', '#4CB4FF', '#F87263', '#FDAF44'],
                series: [{
                    id: 1,
                    type: 'pie',
                    name: 'Механика',
                    innerSize: '50%',
                    data: data,
                    dataLabels: {
                        enabled: false
                    }
                }]
            };
            ws_globals['PIE_BIG_EQUIPMENT'] = new Highcharts.Chart(chart_opt);
        }";
        $m["PIE_BIG_PIPELINE"] = "function (ths, panel, val) {
            val = val || {};
            var data = " . str_replace("\"", "'", $dataForWsFunc['ws_func']['stateEquipMeh']['stateStat']) . ";
            var text = '" . $dataForWsFunc['ws_func']['stateEquipMeh']['stateStat_point'] . " <span style=\"display: inline-block; color: #484B45; font-size: 12px;\"> " . $dataForWsFunc['ws_func']['stateEquipMeh']['stateStat_max'] . "</span>';
            if (typeof val.data != 'undefined'){
                data = jQuery.parseJSON(val.data.stateStat);
                text = val.data.stateStat_point+'<span style=\"display: inline-block; margin-top: 5px; color: #484B45; font-size: 12px;\"> '+val.data.stateStat_max+'</span>';
            }
            var chart_opt = {
                exporting: {
                    enabled: false,
                    url: 'http://127.0.0.1/lib/highcharts/modules/index.php'
                },
                chart: {
                    renderTo: 'pieBigPipeline',
                    backgroundColor: 'transparent',
                    plotBackgroundColor: null,
                    plotBorderWidth: 0,
                    plotShadow: false
                },
                title: {
                    text: 'Вспомогательное оборудование',
                    style: {
                        color: '#8A8A8A',
                        fontFamily: 'PT Sans Regular, sans-serif',
                        fontSize: '12px !important',
                        textTransform: 'uppercase',
                        fontWeight: 'bold'
                    },
                    y: 20
                },
                subtitle: {
                    text: text,
                    align: 'center',
                    verticalAlign: 'middle',
                    style: {
                        color: '#484B45',
                        fontWeight: 'bold',
                        fontSize: '16px'
                    },
                    y: 12
                },
                tooltip: {
                    enabled: true,
                    formatter: function() {
                        return this.point.name + ' — <b>' + this.y + '%</b>';
                    }
                },
                legend: {
                    enabled: true,
                    layout: 'horizontal',
                    verticalAlign: 'bottom',
                    align: 'center',
                    labelFormatter: function () {
                        return this.y + '%';
                    },
                    x: 0,
                    y: 20
                },
                plotOptions: {
                    pie: {
                        dataLabels: {
                            enabled: false,
                            distance: -50,
                            style: {
                                fontWeight: 'bold',
                                color: 'white'
                            }
                        },
                        startAngle: 0,
                        endAngle: 360,
                        center: ['50%', '50%'],
                        showInLegend: true
                    },
                    series: {
                        states: {
                            hover: {
                                enabled: true
                            }
                        },
                        point: {
                            events: {
                                legendItemClick: function () {
                                    return false;
                                }
                            }
                        }
                    }
                },
                colors: ['#92CF50', '#4CB4FF', '#F87263', '#FDAF44'],
                series: [{
                    id: 1,
                    type: 'pie',
                    name: 'Механика',
                    innerSize: '50%',
                    data: data,
                    dataLabels: {
                        enabled: false
                    }
                }]
            };
            ws_globals['PIE_BIG_PIPELINE'] = new Highcharts.Chart(chart_opt);
        }";
        return $m;
    }
}
