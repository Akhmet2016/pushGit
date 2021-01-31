<?php
require_once("./lib/mpdf/vendor/autoload.php");

class ws_cnoPage_workOrderMonthPdfMaker
{
    function __construct($v)
    {
        $this->mapClass($v);
    }

    function mapClass($v)
    {
        $this->loader = $this->loader = $v['loader'];
        $this->model = $this->loader->model;
        $this->dmodel = $this->loader->dataModel;
        $this->amodel = $this->loader->advModel;
        $setting = $this->loader->core->getSettingData();
        $db = $setting['sys']['db'];
        if (fill($db)) {
            $connect = $setting['connect'][$db];
            $this->loader->db(['type' => $connect['type']])->connect([
                'host' => $connect['host'],
                'user' => $connect['user'],
                'pass' => $connect['pass'],
                'db' => $connect['db']
            ]);
        }
    }

    function getStyle()
    {
        $style = '<style>
           body {
                font-family: Arial, Helvetica, sans-serif;
            }
            table {
                border-collapse: collapse;
            }
            table tr {
                border-width: 0;
            }
            table td {
                border: 0 solid #000;
            }
        </style>';
        return ['style' => $style];
    }

    function getHtml($v)
    {
        $html = $this->getStyle()['style'];
        if (fill($v['op_id_array'])) {
            $oper_control = [];
            $user_id_array = [];
            $sub_oper = [];
            $zip = [];
            $risk = [];
            $priority_info = [];
            $service = [1 => 'УТОиР', 2 => 'УМАСиТ', 3 => 'УОиРЭ'];
            $discipline = [1 => 'Механика', 2 => 'Метролигия', 3 => 'Энергетика'];
            $op_id_array = array_unique($v['op_id_array']);
            $base_logo_path = './template/asodu/img/mnemo/';
            $oper_type_dict = $this->dmodel->getComboDictionItem(['code' => 'cno_ppr_type_oper'])['data'];
            $priora = $this->dmodel->getComboDictionItem(['code' => "cno_priority_info"])['data'];
            $sub_oper_type_dict = [];
            $state_dict = $this->dmodel->getComboDictionItem(['code' => 'cno_state'])['data'];
            $discip = $this->dmodel->getComboDictionItem(['code' => 'cno_discipline_type'])['data'];
            $equipType = $this->dmodel->getComboDictionItem(['code' => 'cno_equip_type'])['data'];
            $vazhn = $this->dmodel->getComboDictionItem(['code' => 'cno_vazhn'])['data'];
            $izg = $this->dmodel->getComboDictionItem(['code' => 'cno_zav_izg'])['data'];
            $comboType = $this->dmodel->getComboDictionItem(['code' => "cno_state"])['data'];
            $dict = $this->model->sel_obj(['f' => ['code' => 'cno_org'], 'outtype' => 'single']);
            $ceh = $this->model->sel_diction(['f' => ['obj_id' => $dict['obj_id']], 'out' => 'valuenum', 'outtype' => 'single']);
            $oper_type_comment_dict = $this->dmodel->getComboDictionItem(['code' => 'cno_ppr_type_oper_comment'])['data'];
            $operType = $this->loader->cnoModel->getPprOperType()['value'];
            $ppr_excluded = $this->model->sel_cno_ppr_excludedV(['dir' => ['all' => true], 's' => 'ppr_id']);
            $oper = $this->model->sel_cno_ppr_operation(['f' => ['op_id' => $op_id_array], 'out' => 'op_id', 'outtype' => 'single']);
            foreach ($oper as $key => $item) {
                if (in_array($item['ppr_id'],$ppr_excluded)) {
                    unset($oper[$key]);
                    if(($key = array_search($item['op_id'],$op_id_array)) !== false) unset($op_id_array[$key]);
                }
            }
            //$oper = $this->model->sel_cno_ppr_operation(['s' => ['equip_id','op_type','comment','fact_date','fict','op_id'], 'f' => ['op_id' => $op_id_array], 'out' => 'op_id', 'outtype' => 'single']);
            if (fill($oper)) {
                foreach ($oper as $o => $oitem) {
                    $equip_id_array[] = $oitem['equip_id'];
                    $op_type_array[] = $oitem['op_type'];
                    if ($this->model->sel_cno_toir_operation_complex(['s' => 'comp_id', 'f' => ['parent_id' => $oitem['equip_id'], 'parent_to' => $oitem['op_type']]]))
                        $complex[$o] = true;
                }

                ////////////////////////////////////////////////////////////////////////////////////////////////////////
                $operer = $this->model->sel_cno_ppr_operation(['f' => ['equip_id' => $equip_id_array], 'out' => ['equip_id','op_id'], 'outtype' => 'single']);
                $narabTo = [];
                foreach ($operer as $eq => $eqs) {
                    foreach ($eqs as $key => $value) {
                        if (substr($operType[$value['op_type']]['code'], 0,2) == 'to' && fill($operType[$value['op_type']]['shortname']) && fill($value['fact_date'])) {
                            $narabTo[$eq][$key]['prioritet'] = $operType[$value['op_type']]['shortname'];
                            $narabTo[$eq][$key]['name'] = $operType[$value['op_type']]['name'];
                            $narabTo[$eq][$key]['date'] = $value['fact_date'];
                        }
                    }
                }
                ////////////////////////////////////////////////////////////////////////////////////////////////////////
                if (fill($equip_id_array) && fill($op_type_array)) {
                    $par_id = $this->model->sel_parV(['s' => 'par_id', 'f' => ['obj_id' => $equip_id_array, 'par_type_code' => 'cno_narab_day'], 'out' => 'obj_id', 'outtype' => 'single']);
                    $equipInfo = $this->model->sel_objV(['f' => ['obj_id' => $equip_id_array], 'outtype' => 'single', 'out' => 'obj_id']);
                    //////////////////////////////
                    $rel = $this->model->sel_rel_objv(['f' => ['obj_id' => $equip_id_array, 'rel_type_id' => 64]]);
                    foreach ($rel as $r => $ritem) {
                        $org_id[$ritem['obj_id']] = array_reverse($ritem['tree'])[1];
                    }
                    //////////////////////////////
                    $priority_info_array = $this->model->sel_cno_priority_info(['f' => ['equip_id' => $equip_id_array], 'out' => ['equip_id', 'pr_id'], 'outtype' => 'single']);
                    foreach ($priority_info_array as $pr => $pritem) {
                        foreach ($pritem as $pr2 => $pritem2) {
                            $user_id_array[] = $pritem2['user_id'];
                        }
                    }
                    //////////////////////////////
                    //////////////////////////////
                    $laspOp = $this->model->sel_cno_ppr_operation_last(['f' => ['equip_id' => $equip_id_array], 'out' => 'equip_id']);
                    foreach ($equip_id_array as $e => $eitem) {
                        $maxDate[$eitem] = 0;
                        foreach ($laspOp[$eitem] as $key => $value) {
                            if ($maxDate[$eitem] < strtotime($value['last_date'])) {
                                $maxDate[$eitem] = strtotime($value['last_date']);
                                $last_op_type[$eitem] = $value['op_type'];
                            }
                        }
                        $operLast[$eitem] = $operType[$last_op_type[$eitem]]['name'];
                        $maxDate[$eitem] = date("d.m.Y",$maxDate[$eitem]);
                    }
                    //////////////////////////////
                    $names = $this->model->sel_objv(['s' => ['name', 'obj_type_name', 'code'], 'f' => ['obj_id' => array_merge($user_id_array,$equip_id_array, $org_id)], 'out' => 'obj_id', 'outtype' => 'single']);
                    //////////////////////////////
                    $risk_par_array = $this->model->sel_cno_risk_diapV(['f' => ['obj_id' => $equip_id_array, 'isParRisk' => 1], 'out' => ['obj_id', 'par_type_id'], 'outtype' => 'single']);
                    $unset_par_type_id = $this->model->sel_par_type(['s' => 'par_type_id', 'f' => ['code' => ['cno_risk_to', 'cno_state']], 'out' => 'code']);
                    foreach ($risk_par_array as $rp => $rpitem) {
                        foreach ($rpitem as $rp2 => $rpitem2) {
                            if (!in_array($rp2, $unset_par_type_id['cno_risk_to'])) {
                                $par_type_id_array[] = $rp2;
                                if (in_array($rp2, $unset_par_type_id['cno_state'])) {
                                    $risk_par_array[$rp][$rp2]['valuestr'] = $state_dict[$risk_par_array[$rp][$rp2]['valuenum']];
                                } else {
                                    $risk_par_array[$rp][$rp2]['valuestr'] = $risk_par_array[$rp][$rp2]['valuenum'];
                                }
                            } else {
                                unset($risk_par_array[$rp][$rp2]);
                            }
                        }
                    }
                    $unit_name_array = $this->model->sel_units(['s' => 'name', 'dir' => ['all' => true], 'out' => 'unit_id', 'outtype' => 'single']);
                    $unit_name_array_zip = $this->loader->cnoModel->getComboUom()['data'];
                    if (fill($par_type_id_array)) {
                        $par_type = $this->model->sel_par_type(['s' => ['name', 'unit_id'], 'f' => ['par_type_id' => $par_type_id_array], 'out' => 'par_type_id', 'outtype' => 'single']);
                    }
                    //////////////////////////////
                    $sub_oper_array = $this->model->sel_cno_ppr_sub_operation(['s' => ['type_sub_op', 'labor_costs', 'id_sub_op'], 'f' => ['obj_id' => $equip_id_array], 'out' => ['obj_id', 'op_type', 'id_sub_op'], 'outtype' => 'single']);
                    //////////////////////////////
                    $zip_array = $this->model->sel_cno_toir_zip_plan(['f' => ['equip_id' => $equip_id_array], 'out' => ['equip_id', 'type_oper', 'zip_id'], 'outtype' => 'single']);
                    foreach ($zip_array as $zp => $zpitem) {
                        foreach ($zpitem as $zp2 => $zpitem2) {
                            foreach ($zpitem2 as $z => $zitem) {
                                if (fill($zitem['expense_plan']) || fill($zitem['amount'])) {
                                    switch ($zitem['organization']) {
                                        case 'org_zndh':
                                            switch ($zitem['category']) {
                                                case 1:
                                                    $zip_zndh_gsm[] = $zitem['zd_id'];
                                                    break;
                                                case 2:
                                                    $zip_zndh_consumbales[] = $zitem['zd_id'];
                                                    break;
                                                case 3:
                                                    $zip_zndh_spares[] = $zitem['zd_id'];
                                                    break;
                                            }
                                            break;
                                        case 'org_znds':
                                            switch ($zitem['category']) {
                                                case 1:
                                                    $zip_znds_gsm[] = $zitem['zd_id'];
                                                    break;
                                                case 2:
                                                    $zip_znds_consumbales[] = $zitem['zd_id'];
                                                    break;
                                                case 3:
                                                    $zip_znds_spares[] = $zitem['zd_id'];
                                                    break;
                                            }
                                            break;
                                        case 'root_obj_rvp':
                                            switch ($zitem['category']) {
                                                case 1:
                                                    $zip_rvp_gsm[] = $zitem['zd_id'];
                                                    break;
                                                case 2:
                                                    $zip_rvp_consumbales[] = $zitem['zd_id'];
                                                    break;
                                                case 3:
                                                    $zip_rvp_spares[] = $zitem['zd_id'];
                                                    break;
                                            }
                                            break;
                                    }
                                } else {
                                    unset($zip_array[$zp][$zp2][$z]);
                                }
                            }
                        }
                    }
                    if (fill($zip_zndh_gsm)) {
                        $zip_zndh_gsm_dict = $this->model->sel_cno_diction_zip_gsm(['f' => ['zd_id' => $zip_zndh_gsm], 'out' => 'zd_id', 'outtype' => 'single']);
                    }
                    if (fill($zip_zndh_consumbales)) {
                        $zip_zndh_consumbales_dict = $this->model->sel_cno_diction_zip_consumbales(['f' => ['zd_id' => $zip_zndh_consumbales], 'out' => 'zd_id', 'outtype' => 'single']);
                    }
                    if (fill($zip_zndh_spares)) {
                        $zip_zndh_spares_dict = $this->model->sel_cno_diction_zip_spares(['f' => ['zd_id' => $zip_zndh_spares], 'out' => 'zd_id', 'outtype' => 'single']);
                    }
                    if (fill($zip_znds_gsm)) {
                        $zip_znds_gsm_dict = $this->model->sel_cno_diction_zip_gsm_znds(['f' => ['zd_id' => $zip_znds_gsm], 'out' => 'zd_id', 'outtype' => 'single']);
                    }
                    if (fill($zip_znds_consumbales)) {
                        $zip_znds_consumbales_dict = $this->model->sel_cno_diction_zip_consumbales_znds(['f' => ['zd_id' => $zip_znds_consumbales], 'out' => 'zd_id', 'outtype' => 'single']);
                    }
                    if (fill($zip_znds_spares)) {
                        $zip_znds_spares_dict = $this->model->sel_cno_diction_zip_spares_znds(['f' => ['zd_id' => $zip_znds_spares], 'out' => 'zd_id', 'outtype' => 'single']);
                    }
                    if (fill($zip_rvp_gsm)) {
                        $zip_rvp_gsm_dict = $this->model->sel_cno_diction_zip_gsm_rvp(['f' => ['zd_id' => $zip_rvp_gsm], 'out' => 'zd_id', 'outtype' => 'single']);
                    }
                    if (fill($zip_rvp_consumbales)) {
                        $zip_rvp_consumbales_dict = $this->model->sel_cno_diction_zip_consumbales_rvp(['f' => ['zd_id' => $zip_rvp_consumbales], 'out' => 'zd_id', 'outtype' => 'single']);
                    }
                    if (fill($zip_rvp_spares)) {
                        $zip_rvp_spares_dict = $this->model->sel_cno_diction_zip_spares_rvp(['f' => ['zd_id' => $zip_rvp_spares], 'out' => 'zd_id', 'outtype' => 'single']);
                    }
                    $mest_ustan = [];
                    $equipTree = $this->model->sel_rel(['f' => ['obj_id' => $equip_id_array, 'rel_type_id' => 64], 'outtype' => 'single', 'out' => 'obj_id']);
                    foreach ($equip_id_array as $e => $eitem) {
                        $objInUst = 0;
                        $objTreeName = [];
                        foreach ($equipTree[$eitem]['tree'] as $d => $ditem) {
                            $objTreeName[] = $this->model->sel_objv(['f' => ['obj_id' => $ditem], 'outtype' => 'single', 's' => ['obj_type_code','obj_id','capt','shortname']]);
                        }
                        for ($sch = (count($objTreeName) - 2); $sch > (count($objTreeName) - 8); $sch--) {
                            if ($objTreeName[$sch]['obj_id'] != $eitem) {
                                if ($objTreeName[$sch]['obj_type_code'] == 'kust') {
                                    $mest_ustan[$eitem] .= $objTreeName[$sch]['capt'] . ' / ';
                                } else {
                                    $mest_ustan[$eitem] .= $objTreeName[$sch]['shortname'] . ' / ';
                                }
                            } else {
                                $objInUst = 1;
                            }
                        }
                        $mest_ustan[$eitem] = mb_substr($mest_ustan[$eitem], 0, -3);
                        if ($objInUst == 1) $mest_ustan[$eitem] = mb_substr($mest_ustan[$eitem], 0, -3);
                    }

                    //////////////////////////////
                    $orgs_logo = $this->model->sel_objv(['s' => 'obj_id', 'f' => ['code' => ['zndh_logo2', 'znds_logo', 'rvp_logo']], 'out' => 'code', 'outtype' => 'single']);
                    foreach ($orgs_logo as $l => $litem) {
                        $logo_obj_id_array[] = $litem;
                    }
                    $logo_src = $this->model->sel_parv(['s' => 'valuestr', 'f' => ['obj_id' => $logo_obj_id_array, 'par_type_code' => 'image_path'], 'out' => 'obj_id', 'outtype' => 'single']);

                    $priority = $this->model->sel_cno_priority_info(['f' => ["equip_id" => $equip_id_array], 'out' => ['equip_id']]);
                    foreach ($equip_id_array as $e => $eitem) {
                        foreach ($priority[$eitem] as $k => $val) {
                            $user_id[] = $val['user_id'];
                        }
                    }
                    $user = $this->model->sel_objV(['s' => 'name','f' => ['obj_id' => $user_id], 'out' => 'obj_id', 'outtype' => 'single']);
                    $obj_type_code = $this->model->sel_objV(['f' => ['obj_id' => $equip_id_array], 'out' => 'obj_id', 's' => 'obj_type_code', 'outtype' => 'single']);
                    $obj_type_code_array = [
                        'cno_pump_ppd',
                        'cno_naszndh',
                        'cno_oen',
                        'cno_compvozdkip',
                        'cno_compaz',
                        'cno_compvozd',
                        'cno_compvozdm',
                        'cno_compnab',
                        'cno_comppn',
                        'cno_vozd',
                        'cno_otventicond',
                        'cno_ventilyat',
                        'cno_vent_gaz_zndh',
                        'cno_vent_no'
                    ];
                    foreach ($equip_id_array as $e => $eitem) {
                        switch ($names[$org_id[$eitem]]['code']) {
                            case 'org_zndh':
                                $image[$eitem] = $base_logo_path.$logo_src[$orgs_logo['zndh_logo2']];
                                $sub_oper_type_dict[$eitem] = $this->model->sel_cno_diction_sub_oper_zndh(['dir' => ['all' => true], 'out' => 'id_sub', 'outtype' => 'single']);
                                break;
                            case 'org_znds':
                                $image[$eitem] = $base_logo_path.$logo_src[$orgs_logo['znds_logo']];
                                $sub_oper_type_dict[$eitem] = $this->model->sel_cno_diction_sub_oper_znds(['dir' => ['all' => true], 'out' => 'id_sub', 'outtype' => 'single']);
                                break;
                            case 'root_obj_rvp':
                                $image[$eitem] = $base_logo_path.$logo_src[$orgs_logo['rvp_logo']];
                                $sub_oper_type_dict[$eitem] = $this->model->sel_cno_diction_sub_oper_rvp(['dir' => ['all' => true], 'out' => 'id_sub', 'outtype' => 'single']);
                                break;
                        }

                        if (fill($priority[$eitem])) {
                            foreach ($priority[$eitem] as $k => $val) {
                                if (fill($priority_info[$eitem])) {
                                    $priority_info[$eitem] .= '<tr style="border: 1px solid black">
                                                            <td style="border: 1px solid black;padding:2px 2px;">' . $user[$val['user_id']] . '</td>
                                                            <td colspan="5" style="border: 1px solid black;padding:2px 2px;">' . $val['text'] . '</td>
                                                            <td colspan="2" style="border: 1px solid black;padding:2px 2px;">' . $priora[$val['priority']] . '</td>
                                                            <td colspan="2" style="border: 1px solid black;padding:2px 2px;">' . substr($val['datetime'], 0, 10) . '</td>
                                                        </tr>';
                                } else {
                                    $priority_info[$eitem] = '<tr style="border: 1px solid black">
                                                            <td style="border: 1px solid black;padding:2px 2px;">' . $user[$val['user_id']] . '</td>
                                                            <td colspan="5" style="border: 1px solid black;padding:2px 2px;">' . $val['text'] . '</td>
                                                            <td colspan="2" style="border: 1px solid black;padding:2px 2px;">' . $priora[$val['priority']] . '</td>
                                                            <td colspan="2" style="border: 1px solid black;padding:2px 2px;">' . substr($val['datetime'], 0, 10) . '</td>
                                                        </tr>';
                                }
                            }
                        } else {
                            for ($q = 0; $q < 5; $q++) {
                                if (fill($priority_info[$eitem])) {
                                    $priority_info[$eitem] .= '<tr style="border: 1px solid black">
                                                                <td style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                <td colspan="5" style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                <td colspan="2" style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                <td colspan="2" style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                           </tr>';
                                } else {
                                    $priority_info[$eitem] = '<tr style="border: 1px solid black">
                                                                <td style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                <td colspan="5" style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                <td colspan="2" style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                <td colspan="2" style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                           </tr>';
                                }
                            }
                        }
                        if (fill($risk_par_array[$eitem])) {
                            foreach ($risk_par_array[$eitem] as $rpa => $rpaitem) {
                                if (fill($risk[$eitem])) {
                                    $risk[$eitem] .= '<tr style="border: 1px solid black">
                                                        <td colspan="3" style="text-align:left;font-size:15px;border: 1px solid black;padding:2px 2px;">' . $par_type[$rpaitem['par_type_id']]['name'] . '</td>
                                                        <td colspan="2" style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">' . $rpaitem['valuestr'] . '</td>
                                                        <td colspan="2" style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;"></td>
                                                        <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">' . (fill($par_type[$rpaitem['par_type_id']]['unit_id']) ? $unit_name_array[$par_type[$rpaitem['par_type_id']]['unit_id']] : ' ') . '</td>
                                                        <td colspan="2" style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;"></td>
                                                    </tr>';
                                } else {
                                    $risk[$eitem] = '<tr style="border: 1px solid black">
                                                       <td colspan="3" style="text-align:left;font-size:15px;border: 1px solid black;padding:2px 2px;">' . $par_type[$rpaitem['par_type_id']]['name'] . '</td>
                                                        <td colspan="2" style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">' . $rpaitem['valuestr'] . '</td>
                                                        <td colspan="2" style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;"></td>
                                                        <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">' . (fill($par_type[$rpaitem['par_type_id']]['unit_id']) ? $unit_name_array[$par_type[$rpaitem['par_type_id']]['unit_id']] : ' ') . '</td>
                                                        <td colspan="2" style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;"></td>
                                                    </tr>';
                                }
                            }
                        } else {
                            for ($q = 0; $q < 5; $q++) {
                                if (fill($risk[$eitem])) {
                                    $risk[$eitem] .= '<tr style="border: 1px solid black">
                                                        <td colspan="3" style="text-align:left;font-size:15px;border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                        <td colspan="2" style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;"></td>
                                                        <td colspan="2" style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;"></td>
                                                        <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                        <td colspan="2" style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                    </tr>';
                                } else {
                                    $risk[$eitem] = '<tr style="border: 1px solid black">
                                                        <td colspan="3" style="text-align:left;font-size:15px;border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                        <td colspan="2" style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;"></td>
                                                        <td colspan="2" style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;"></td>
                                                        <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                        <td colspan="2" style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                    </tr>';
                                }
                            }
                        }
                    }
                    //////////////////////////////
                    $date = date('Y-m-d 00:00:00');
                    $risk_matr_DO = $this->model->sel_cno_dashboard_risk_DO(['s' => 'value', 'f' => ['obj_id' => $equip_id_array, 'direction' => 'riskMeh', 'datetime' => $date], 'out' => 'obj_id', 'outtype' => 'single']);
                    $equip_params = $this->model->sel_parv([
                        's' => [
                            'valuestr',
                            'valuenum'
                        ],
                        'f' => [
                            'obj_id' => $equip_id_array,
                            'par_type_code' => [
                                'cno_model',
                                'cno_tech_number',
                                'cno_state',
                                'cno_zav_number',
                                'cno_date_vipusk',
                                'cno_srok_expl_obor',
                                'cno_srok_epb',
                                'cno_tag',
                                'cno_zavod_izg',
                                'cno_type_of_equip',
                                'cno_ceh',
                                'cno_vazhn_obor',
                                'cno_date_vvoda',
                                'cno_srok_expl_obor',
                                'cno_norm_srok',
                                'cno_discipline',
                                'cno_narab',
                                'cno_work_mode',
                                'cno_norm_srok_expl',
                                'cno_mrp'
                            ]
                        ],
                        'out' => [
                            'obj_id',
                            'par_type_code'
                        ],
                        'outtype' => 'single'
                    ]);
                    /////////////////////////////

                    foreach ($oper as $o2 => $oitem2) {
                        if (fill($sub_oper_array[$oitem2['equip_id']][$oitem2['op_type']])) {
                            foreach ($sub_oper_array[$oitem2['equip_id']][$oitem2['op_type']] as $s => $sitem) {
                                if (fill($sub_oper[$oitem2['equip_id']][$oitem2['op_type']]) && fill($sitem['labor_costs'])) {
                                    $sub_oper[$oitem2['equip_id']][$oitem2['op_type']] .= '<tr style="border: 1px solid black">
                                                                                                <td colspan="3" style="border: 1px solid black;padding:2px 2px;">' . $this->loader->reportCnoModel->getExplodedStrByNum(["str" => $sub_oper_type_dict[$oitem2['equip_id']][$sitem['type_sub_op']]['name'], "num" => 35]) . '</td>
                                                                                                <td style="border: 1px solid black;padding:2px 2px;">' . $discip[$sub_oper_type_dict[$oitem2['equip_id']][$sitem['type_sub_op']]['discipline']] . '</td>
                                                                                                <td style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                                <td style="border: 1px solid black;padding:2px 2px;">' . $sitem['labor_costs'] . '</td>
                                                                                                <td style="border: 1px solid black;padding:2px 2px;"> </td>
                                                                                                <td style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                                <td colspan="2" style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                            </tr>';
                                } elseif (fill($sitem['labor_costs'])) {
                                    $sub_oper[$oitem2['equip_id']][$oitem2['op_type']] = '<tr style="border: 1px solid black">
                                                                                                <td colspan="3" style="border: 1px solid black;padding:2px 2px;">' . $this->loader->reportCnoModel->getExplodedStrByNum(["str" => $sub_oper_type_dict[$oitem2['equip_id']][$sitem['type_sub_op']]['name'], "num" => 35]) . '</td>
                                                                                                <td style="border: 1px solid black;padding:2px 2px;">' . $discip[$sub_oper_type_dict[$oitem2['equip_id']][$sitem['type_sub_op']]['discipline']] . '</td>
                                                                                                <td style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                                <td style="border: 1px solid black;padding:2px 2px;">' . $sitem['labor_costs'] . '</td>
                                                                                                <td style="border: 1px solid black;padding:2px 2px;"> </td>
                                                                                                <td style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                                <td colspan="2" style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                            </tr>';
                                }
                            }
                        } else {
                            for ($q = 0; $q < 5; $q++) {
                                if (fill($sub_oper[$oitem2['equip_id']][$oitem2['op_type']])) {
                                    $sub_oper[$oitem2['equip_id']][$oitem2['op_type']] .= '<tr style="border: 1px solid black">
                                                                                                <td colspan="3" style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                                <td style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                                <td style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                                <td style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                                <td style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                                <td style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                                <td colspan="2" style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                            </tr>';
                                } else {
                                    $sub_oper[$oitem2['equip_id']][$oitem2['op_type']] = '<tr style="border: 1px solid black">
                                                                                                <td colspan="3" style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                                <td style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                                <td style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                                <td style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                                <td style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                                <td style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                                <td colspan="2" style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                            </tr>';
                                }
                            }
                        }

                        if (fill($zip_array[$oitem2['equip_id']][$oitem2['op_type']])) {
                            foreach ($zip_array[$oitem2['equip_id']][$oitem2['op_type']] as $z => $zitem) {
                                switch ($zitem['organization']) {
                                    case 'org_zndh':
                                        switch ($zitem['category']) {
                                            case 1:
                                                $zip_item = $zip_zndh_gsm_dict[$zitem['zd_id']];
                                                $cat = 'ГСМ';
                                                break;
                                            case 2:
                                                $zip_item = $zip_zndh_consumbales_dict[$zitem['zd_id']];
                                                $cat = 'Расходные материалы';
                                                break;
                                            case 3:
                                                $zip_item = $zip_zndh_spares_dict[$zitem['zd_id']];
                                                $cat = 'Запасные части';
                                                break;
                                        }
                                        break;
                                    case 'org_znds':
                                        switch ($zitem['category']) {
                                            case 1:
                                                $zip_item = $zip_znds_gsm_dict[$zitem['zd_id']];
                                                $cat = 'ГСМ';
                                                break;
                                            case 2:
                                                $zip_item = $zip_znds_consumbales_dict[$zitem['zd_id']];
                                                $cat = 'Расходные материалы';
                                                break;
                                            case 3:
                                                $zip_item = $zip_znds_spares_dict[$zitem['zd_id']];
                                                $cat = 'Запасные части';
                                                break;
                                        }
                                        break;
                                    case 'root_obj_rvp':
                                        switch ($zitem['category']) {
                                            case 1:
                                                $zip_item = $zip_rvp_gsm_dict[$zitem['zd_id']];
                                                $cat = 'ГСМ';
                                                break;
                                            case 2:
                                                $zip_item = $zip_rvp_consumbales_dict[$zitem['zd_id']];
                                                $cat = 'Расходные материалы';
                                                break;
                                            case 3:
                                                $zip_item = $zip_rvp_spares_dict[$zitem['zd_id']];
                                                $cat = 'Запасные части';
                                                break;
                                        }
                                        break;

                                }

                                if (fill($zip[$oitem2['equip_id']][$oitem2['op_type']])) {
                                    $zip[$oitem2['equip_id']][$oitem2['op_type']] .= '<tr style="border: 1px solid black">
                                                                                        <td style="text-align:left;font-size:15px;border: 1px solid black;padding:2px 2px;">' . $this->loader->reportCnoModel->getExplodedStrByNum(["str" => ($zip_item['name'].' '.(($zip_item['obozn'] == $zip_item['name']) ? '' : $zip_item['obozn'])), "num" => 25]) . '<br></td>
                                                                                        <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">' . $cat . '</td>
                                                                                        <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">' . $unit_name_array_zip[$zip_item['uom']] . '</td>
                                                                                        <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">' . $zip_item['plant_code'] . '</td>
                                                                                        <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">' . $zip_item['nom_code'] . '</td>
                                                                                        <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">' . $zitem['amount'] . '</td>
                                                                                        <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;"></td>
                                                                                        <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                        <td colspan="2" style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                    </tr>';
                                } else {
                                    $zip[$oitem2['equip_id']][$oitem2['op_type']] = '<tr style="border: 1px solid black">
                                                                                        <td style="text-align:left;font-size:15px;border: 1px solid black;padding:2px 2px;">' . $this->loader->reportCnoModel->getExplodedStrByNum(["str" => ($zip_item['name'].' '.(($zip_item['obozn'] == $zip_item['name']) ? '' : $zip_item['obozn'])), "num" => 25]) . '<br></td>
                                                                                        <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">' . $cat . '</td>
                                                                                        <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">' . $unit_name_array_zip[$zip_item['uom']] . '</td>
                                                                                        <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">' . $zip_item['plant_code'] . '</td>
                                                                                        <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">' . $zip_item['nom_code'] . '</td>
                                                                                        <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">' . $zitem['amount'] . '</td>
                                                                                        <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;"></td>
                                                                                        <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                        <td colspan="2" style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                    </tr>';
                                }
                            }
                        } else {
                            for ($q = 0; $q < 5; $q++) {
                                if (fill($zip[$oitem2['equip_id']][$oitem2['op_type']])) {
                                    $zip[$oitem2['equip_id']][$oitem2['op_type']] .= '<tr style="border: 1px solid black">
                                                                                        <td style="text-align:left;font-size:15px;border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                        <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                        <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                        <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                        <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                        <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                        <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                        <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                        <td colspan="2" style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                    </tr>';
                                } else {
                                    $zip[$oitem2['equip_id']][$oitem2['op_type']] = '<tr style="border: 1px solid black">
                                                                                        <td style="text-align:left;font-size:15px;border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                        <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                        <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                        <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                        <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                        <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                        <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                        <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                        <td colspan="2" style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                                                    </tr>';
                                }
                            }
                        }
                    }
                    /////////////////////////////

                    $modelId = $this->model->sel_parV(['s' => 'valuenum', 'f' => ['obj_id' => $equip_id_array, 'par_type_code' => 'cno_model']]);
                    $model = $this->model->sel_parV(['f' => ['obj_id' => $modelId], 'out' => 'obj_id']);
                    foreach ($model as $k => $val) {
                        foreach ($val as $key => $value) {
                            if (fill($value['property']['oper_control'])) {
                                if ($value['property']['oper_control'] === true) {
                                    $par_type_id[$k][] = $value['par_type_id'];
                                }
                            }
                        }
                    }
                    $par = $this->model->sel_parV(['f' => ['obj_id' => $equip_id_array], 'out' => 'obj_id']);
                    foreach ($par as $k => $val) {
                        foreach ($val as $key => $value) {
                            foreach ($par_type_id as $ke => $va) {
                                foreach ($va as $keys) {
                                    if ($value['par_type_id'] == $keys) {
                                        $operControl[$k][] = $value;
                                    }
                                }
                            }
                        }
                    }

                    foreach ($equip_id_array as $key) {
                        if (fill($operControl[$key])) {
                            foreach ($operControl[$key] as $value) {
                                $oper_control[$key] .= '<tr style="border: 1px solid black">
                                                            <td colspan="3" style="border: 1px solid black;padding:2px 2px;">' . $value['name'] . '</td>
                                                            <td colspan="2" style="border: 1px solid black;padding:2px 2px;">' . (fill($value['valuenum']) ? $value['valuenum'] : $value['valuestr']) . '</td>
                                                            <td colspan="2" style="border: 1px solid black;padding:2px 2px;"></td>
                                                            <td style="border: 1px solid black;padding:2px 2px;">' . $unit_name_array[$value['unit_id']] . '</td>
                                                            <td colspan="2" style="border: 1px solid black;padding:2px 2px;"></td>
                                                        </tr>';
                            }
                        }
                        else {
                            for ($q = 0; $q < 5; $q++) {
                                $oper_control[$key] .= '<tr style="border: 1px solid black">
                                                            <td colspan="3" style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                            <td colspan="2" style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                            <td colspan="2" style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                            <td style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                            <td colspan="2" style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                        </tr>';
                            }
                        }
                    }

                    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                    $masKrTr = [];
                    foreach ($operType as $key => $value) {
                        if ($value['code'] == 'rem_tr' || $value['code'] == 'rem_kr' || $value['code'] == 'to_kr' || $value['code'] == 'to_tr') {
                            $masKrTr[] = $value['valuenum'];
                            $masKrTrCode[$value['code']] = $value['valuenum'];
                        }
                    }
                    $kr_tr = $this->model->sel_cno_ppr_operation(['f' => ['equip_id' => $equip_id_array, 'op_type' => $masKrTr], 'out' => ['equip_id','op_type', 'op_id'], 'outtype' => 'single']);
                    $maxKrTr = [];
                    foreach ($equip_id_array as $oneEquip) {
                        foreach ($kr_tr[$oneEquip] as $key => $value) {
                            $check = 0;
                            foreach ($value as $k => $val) {
                                if (fill($val['fact_date'])) {
                                    $check++;
                                    if ($check == 1) {
                                        $maxKrTr[$oneEquip][$key] = $val['fact_date'];
                                    }
                                    if (strtotime($maxKrTr[$key]) > strtotime($val['fact_date'])) {
                                        $maxKrTr[$oneEquip][$key] = $val['fact_date'];
                                    }
                                }
                            }
                        }
                        foreach ($maxKrTr[$oneEquip] as $key => $value) {
                            (fill($equip_params[$oneEquip]['cno_work_mode']['valuenum'])) ?  $ours = $equip_params[$oneEquip]['cno_work_mode']['valuenum'] : $ours = 24;
                            $dayKrTr[$oneEquip][$key] = ((strtotime($date) - strtotime($value)) / (3600 * 24)) * $ours;
                        }
                        foreach ($masKrTrCode as $cod => $znach) {
                            if (fill( $dayKrTr[$oneEquip][$znach])) {
                                if ($cod == 'rem_tr' || $cod == 'to_tr') {
                                    $tr[$oneEquip] = $dayKrTr[$oneEquip][$znach];
                                } else {
                                    $kr[$oneEquip] = $dayKrTr[$oneEquip][$znach];
                                }
                            }
                        }
                    }
                    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

                    /////////////////////////////
                    foreach ($op_id_array as $i => $item) {
                        ////////////////////////////////////////////////////////////////////////////////////////////////////////////
                        $myKey = (int)$item;
                        foreach ($narabTo as $nar => $nars) {
                            foreach ($nars as $kluch => $znach) {
                                if (fill($operType[$oper[$item]['op_type']]['shortname'])) {
                                    if (($myKey != $kluch) && ((int)$znach['prioritet'] >= (int)$operType[$oper[$myKey]['op_type']]['shortname'])) {
                                        $masNarab[$myKey][] = $znach;
                                    }
                                } else {
                                    if ($myKey != $kluch) {
                                        $masNarab[$myKey][] = $znach;
                                    }
                                }
                            }
                        }
                        $maxDateNarab[$myKey] = strtotime($masNarab[$myKey][0]['date']);
                        $narabAfterTo[$myKey] = $masNarab[$myKey][0];
                        foreach ($masNarab as $nar => $nars) {
                            foreach ($nars as $kluch => $znach) {
                                if (strtotime($znach['date']) > $maxDate) {
                                    $maxDateNarab[$myKey] = strtotime($znach['date']);
                                    $narabAfterTo[$myKey] = $znach;
                                }
                            }
                        }
                        $oilOPer = $this->model->sel_dictV(['f' => ['dict_code' => 'cno_ppr_type_oper', 'code' => 'quantity_oil'], 'outtype' => 'single', 's' => 'valuenum']);
                        $oilArray = $this->model->sel_cno_ppr_operation(['f' => ['equip_id' => $equip_id_array, 'op_type' => $oilOPer], 's' => 'fact_date', 'out' => 'equip_id']);
                        if (fill($oilArray)) {
                            foreach ($oilArray as $oilKey => $oilValue) {
                                $maxOildate[$myKey] = strtotime($oilArray[$oilKey][0]);
                                foreach ($oilValue as $oilK => $oilV) {
                                    if (strtotime($oilV) > $maxOildate[$myKey])
                                        $maxOildate[$myKey] = strtotime($oilV);
                                }
                            }
                        }
                        if (fill($par_id[$oper[$item]['equip_id']])) {
                            $histMas[$myKey] = $this->model->sel_hist(['s' => 'valuenum', 'f' => ['par_id' => $par_id[$oper[$item]['equip_id']],
                                'timeend' => ['field' => "valuedate", 'val' => date("Y-m-d H:i:s", strtotime($date)), "operate" => "<="],
                                'timestart' => ['field' => "valuedate", 'val' => date("Y-m-d H:i:s", strtotime($narabAfterTo[$myKey]['date'])), "operate" => ">="]]]);
                            $sumNarabTo = [];
                            foreach ($histMas[$myKey] as $histKey => $histValue) {
                                $sumNarabTo[$myKey] = $sumNarabTo[$myKey] + $histValue;
                            }
                            if (fill($sumNarabTo[$myKey])) $afterTo[$myKey] = round($sumNarabTo[$myKey],0);
                            if (fill($maxOildate[$myKey])) {
                                $histMasOil[$myKey] = $this->model->sel_hist(['s' => 'valuenum', 'f' => ['par_id' => $par_id[$oper[$item]['equip_id']],
                                    'timeend' => ['field' => "valuedate", 'val' => date("Y-m-d H:i:s", strtotime($date)), "operate" => "<="],
                                    'timestart' => ['field' => "valuedate", 'val' => date("Y-m-d H:i:s", $maxOildate[$myKey]), "operate" => ">="]]]);
                                $sumNarabOil = [];
                                foreach ($histMasOil[$myKey] as $histK => $histV) $sumNarabOil[$myKey] = $sumNarabOil[$myKey] + $histV;
                                if (fill($sumNarabOil[$myKey])) $afterOil[$myKey] = round($sumNarabOil[$myKey], 0);
                            }
                        }
                        ////////////////////////////////////////////////////////////////////////////////////////////////////////////
                        if (fill($equip_params[$oper[$item]['equip_id']]['cno_norm_srok']['valuestr'])) {
                            $pasp = $equip_params[$oper[$item]['equip_id']]['cno_norm_srok']['valuestr'].$this->getDateText($equip_params[$oper[$item]['equip_id']]['cno_norm_srok']['valuenum']);
                            $srav = $equip_params[$oper[$item]['equip_id']]['cno_norm_srok']['valuestr'];
                        } else {
                            (fill($equip_params[$oper[$item]['equip_id']]['cno_norm_srok_expl']['valuestr']) ? $pasp = $equip_params[$oper[$item]['equip_id']]['cno_norm_srok_expl']['valuestr'].$this->getDateText($equip_params[$oper[$item]['equip_id']]['cno_norm_srok_expl']['valuenum']) : $pasp = '-');
                            $srav = $equip_params[$oper[$item]['equip_id']]['cno_norm_srok_expl']['valuestr'];
                        }
                        if (fill($equip_params[$oper[$item]['equip_id']]['cno_srok_expl_obor']['valuestr']) && fill($srav)) {
                            if ($equip_params[$oper[$item]['equip_id']]['cno_srok_expl_obor']['valuestr'] > $srav) {
                                $txtFactSlujb = "Истек";
                                $color = 'f00';
                            } else {
                                $txtFactSlujb = $equip_params[$oper[$item]['equip_id']]['cno_srok_expl_obor']['valuestr'] . $this->getDateText($equip_params[$oper[$item]['equip_id']]['cno_srok_expl_obor']['valuestr']);
                            }
                        } else {
                            $txtFactSlujb = $equip_params[$oper[$item]['equip_id']]['cno_srok_expl_obor']['valuestr'] . $this->getDateText($equip_params[$oper[$item]['equip_id']]['cno_srok_expl_obor']['valuestr']);
                        }
                        if (fill($oper_type_comment_dict[$oper[$item]['op_type']])) {
                            $info = ' - ' . $oper_type_comment_dict[$oper[$item]['op_type']];
                        } else {
                            $info = '';
                        }
                        ($oper[$item]['fict'] === false) ? $planOrNot = 'плановое' : $planOrNot = 'внеплановое';

                        $html .= '<table class="ws_report_table" style="text-align:center;table-layout:fixed;line-height: normal;">
                                    <tbody>
                                        <tr>
                                            <td colspan="5" rowspan="4" style="text-align:left;border-width:0px;padding-top:10px;padding-right:0px;padding-bottom:0px;padding-left:0px;">
                                                <img width="220px" src="'.$image[$oper[$item]['equip_id']].'">
                                            </td>
                                            <td style="border-width:0px;padding:2px 2px;">&nbsp;</td>
                                            <td style="border-width:0px;padding:2px 2px;">&nbsp;</td>
                                            <td colspan="2" style="text-align:center;font-size:18px;border-width:0px;padding-top:10px;padding-right:0px;padding-bottom:10px;padding-left:0px;">
                                                Заказ-наряд<br>от ' .date("d.m.Y", time()). '
                                            </td>
                                            <td style="text-align:left;font-size:18px;border-width:0px;padding-top:10px;padding-right:0px;padding-bottom:10px;padding-left:0px;">
                                                № ' . date("Y", time()).$item . '
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="border-width:0px;padding:2px 2px;">&nbsp;</td>
                                            <td style="border-width:0px;padding:2px 2px;">&nbsp;</td>
                                            <td colspan="2" style="text-align:center;font-size:12px;border-width:0px;padding:2px 2px;">
                                                Агрегатор<br>(координатор)
                                            </td>
                                            <td style="text-align:left;font-size:12px;border-width:0px;padding:2px 2px;">
                                                УТОИР
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="border-width:0px;padding:2px 2px;">&nbsp;</td>
                                            <td style="border-width:0px;padding:2px 2px;">&nbsp;</td>
                                            <td colspan="2" style="text-align:center;font-size:12px;border-width:0px;padding:2px 2px;">
                                                Участники по<br>дисциплинам
                                            </td>
                                            <td style="text-align:left;font-size:12px;border-width:0px;padding:2px 2px;">
                                                ' . (fill($equip_params[$oper[$item]['equip_id']]['cno_discipline']['valuestr']) ? $discip[$equip_params[$oper[$item]['equip_id']]['cno_discipline']['valuestr']] : '-') . '
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="border-width:0px;padding:2px 2px;">&nbsp;</td>
                                            <td style="border-width:0px;padding:2px 2px;">&nbsp;</td>
                                            <td colspan="2" style="text-align:center;font-size:12px;border-width:0px;padding:2px 2px;">
                                                Приоритет<br>исполнения
                                            </td>
                                            <td style="text-align:left;font-size:12px;border-width:0px;padding:2px 2px;">&nbsp;</td>
                                        </tr>
                                        <tr>

                                        </tr>
                                        <tr>
                                            <td colspan="10" style="text-align:left;font-size:18px;min-width:40px;padding:2px 2px;">Информация об оборудовании</td>
                                        </tr>
                                        <tr>

                                        </tr>
                                        <tr>
                                            <td colspan="2" style="text-align:left;font-size:15px;font-weight:bold;padding:2px 2px;">
                                                Место установки
                                            </td>
                                            <td colspan="8" style="text-align:left;font-size:15px;padding:2px 2px;">
                                                : '. $mest_ustan[$oper[$item]['equip_id']] .'
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" style="text-align:left;font-size:15px;font-weight:bold;padding:2px 2px;">
                                                Технологический номер
                                            </td>
                                            <td colspan="3" style="text-align:left;font-size:15px;padding:2px 2px;">
                                                : ' . (fill($equip_params[$oper[$item]['equip_id']]['cno_tech_number']['valuestr']) ? $equip_params[$oper[$item]['equip_id']]['cno_tech_number']['valuestr'] : '-') . '
                                            </td>
                                            <td colspan="2" style="text-align:left;font-size:15px;font-weight:bold;padding:2px 2px;">
                                                Таг №
                                            </td>
                                            <td colspan="3" style="text-align:left;font-size:15px;padding:2px 2px;">
                                                : ' . (fill($equip_params[$oper[$item]['equip_id']]['cno_tag']['valuestr']) ? $equip_params[$oper[$item]['equip_id']]['cno_tag']['valuestr'] : '-') . '
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" style="text-align:left;font-size:15px;font-weight:bold;padding:2px 2px;">
                                                Наименование типа
                                            </td>
                                            <td colspan="3" style="text-align:left;font-size:15px;padding:2px 2px;">
                                                : '.$names[$oper[$item]['equip_id']]['obj_type_name'].'
                                            </td>
                                            <td colspan="2" style="text-align:left;font-size:15px;font-weight:bold;padding:2px 2px;">
                                                Тип оборудования
                                            </td>
                                            <td colspan="3" style="text-align:left;font-size:15px;padding:2px 2px;">
                                                : ' . (fill($equip_params[$oper[$item]['equip_id']]['cno_type_of_equip']['valuenum']) ? $equipType[$equip_params[$oper[$item]['equip_id']]['cno_type_of_equip']['valuenum']] : '-') . '
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" style="text-align:left;font-size:15px;font-weight:bold;padding:2px 2px;">
                                                Марка
                                            </td>
                                            <td colspan="8" style="text-align:left;font-size:15px;padding:2px 2px;">
                                                : ' . (fill($equipInfo[$oper[$item]['equip_id']]['name']) ? $equipInfo[$oper[$item]['equip_id']]['name'] : '-') . '
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" style="text-align:left;font-size:15px;font-weight:bold;padding:2px 2px;">
                                                Завод-изготовитель
                                            </td>
                                            <td colspan="8" style="text-align:left;font-size:15px;padding:2px 2px;">
                                                : ' . (fill($equip_params[$oper[$item]['equip_id']]['cno_zavod_izg']['valuestr']) ? $izg[$equip_params[$oper[$item]['equip_id']]['cno_zavod_izg']['valuestr']] : '-') . '
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" style="text-align:left;font-size:15px;font-weight:bold;padding:2px 2px;">
                                                Категория оборудования
                                            </td>
                                            <td colspan="3" style="text-align:left;font-size:15px;padding:2px 2px;">
                                                : ' . (fill($equip_params[$oper[$item]['equip_id']]['cno_vazhn_obor']['valuestr']) ? $vazhn[$equip_params[$oper[$item]['equip_id']]['cno_vazhn_obor']['valuestr']] : '-') . '
                                            </td>
                                            <td colspan="2" style="text-align:left;font-size:15px;font-weight:bold;padding:2px 2px;">
                                                Статус
                                            </td>
                                            <td colspan="3" style="text-align:left;font-size:15px;padding:2px 2px;">
                                                : ' . (fill($ceh[$equip_params[$oper[$item]['equip_id']]['cno_ceh']['valuestr']]['code']) == 'pnr' ? "ПНР" : 'Введено') . '
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" style="text-align:left;font-size:15px;font-weight:bold;padding:2px 2px;">
                                                Состояние
                                            </td>
                                            <td colspan="8" style="text-align:left;font-size:15px;padding:2px 2px;">
                                                : ' . (fill($equip_params[$oper[$item]['equip_id']]['cno_state']['valuestr']) ? $comboType[$equip_params[$oper[$item]['equip_id']]['cno_state']['valuestr']] : '-') . '
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" style="text-align:left;font-size:15px;font-weight:bold;padding:2px 2px;">
                                                Заводской номер
                                            </td>
                                            <td colspan="8" style="text-align:left;font-size:15px;padding:2px 2px;">
                                                : ' . (fill($equip_params[$oper[$item]['equip_id']]['cno_zav_number']['valuestr']) ? $equip_params[$oper[$item]['equip_id']]['cno_zav_number']['valuestr'] : '-') . '
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" style="text-align:left;font-size:15px;font-weight:bold;padding:2px 2px;">
                                                Дата выпуска
                                            </td>
                                            <td colspan="3" style="text-align:left;font-size:15px;padding:2px 2px;">
                                                : ' . (fill($equip_params[$oper[$item]['equip_id']]['cno_date_vipusk']['valuestr']) ? $equip_params[$oper[$item]['equip_id']]['cno_date_vipusk']['valuestr'] : '-') . '
                                            </td>
                                            <td colspan="2" style="text-align:left;font-size:15px;font-weight:bold;padding:2px 2px;">
                                                Дата ввода в эксплуатацию
                                            </td>
                                            <td colspan="3" style="text-align:left;font-size:15px;padding:2px 2px;">
                                                : ' . (fill($equip_params[$oper[$item]['equip_id']]['cno_date_vvoda']['valuestr']) ? $equip_params[$oper[$item]['equip_id']]['cno_date_vvoda']['valuestr'] : '-') . '
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" style="text-align:left;font-size:15px;font-weight:bold;padding:2px 2px;">
                                                Срок службы по паспорту
                                            </td>
                                            <td colspan="3" style="text-align:left;font-size:15px;padding:2px 2px;">
                                                :  ' . $pasp . '
                                            </td>
                                            <td colspan="2" style="text-align:left;font-size:15px;font-weight:bold;padding:2px 2px;">
                                                Срок службы фактический
                                            </td>
                                            <td colspan="3" style="text-align:left;font-size:15px;padding:2px 2px;color:'. $color .'">
                                                : ' . $txtFactSlujb . '
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" style="text-align:left;font-size:15px;font-weight:bold;padding:2px 2px;">
                                                Срок продления по ЭПБ
                                            </td>
                                            <td colspan="8" style="text-align:left;font-size:15px;padding:2px 2px;">
                                                : ' . (fill($equip_params[$oper[$item]['equip_id']]['cno_srok_epb']['valuestr']) ? $equip_params[$oper[$item]['equip_id']]['cno_srok_epb']['valuestr'] : '-') . '
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" style="text-align:left;font-size:15px;font-weight:bold;padding:2px 2px;">
                                                Вероятность отказа
                                            </td>
                                            <td style="text-align:left;font-size:15px;padding:2px 2px;">
                                                : ' . (fill($risk_matr_DO[$oper[$item]['equip_id']]) ? $risk_matr_DO[$oper[$item]['equip_id']] . ' %' : '-') . '
                                            </td>
                                            <td style="text-align:center;font-size:15px;font-weight:bold;padding:2px 2px;">
                                                на
                                            </td>
                                            <td style="text-align:left;font-size:15px;padding:2px 2px;">
                                                ' .date("d.m.Y", time()). '
                                            </td>
                                            <td style="text-align:center;font-size:15px;font-weight:bold;padding:2px 2px;">
                                                параметр риска:
                                            </td>
                                            <td colspan="4" style="text-align:center;font-size:15px;border-width:0px;padding:2px 2px;">&nbsp;</td>
                                        </tr>
                                        <tr><td style="border-width:0px;padding:2px 2px;">&nbsp;</td></tr>
                                        <tr>
                                            <td colspan="10" style="text-align:left;font-size:18px;min-width:40px;padding:2px 2px;">Блок приоритетной информации</td>
                                        </tr>
                                        <tr style="border: 1px solid black">
                                            <td style="border: 1px solid black;text-align:center;font-size:15px;font-weight:bold;min-width:40px;padding:2px 2px;">Пользователь</td>
                                            <td colspan="5" style="border: 1px solid black;text-align:center;font-size:15px;font-weight:bold;padding:2px 2px;">Информация</td>
                                            <td colspan="2" style="border: 1px solid black;text-align:center;font-size:15px;font-weight:bold;min-width:40px;padding:2px 2px;">Приоритет</td>
                                            <td colspan="2" style="border: 1px solid black;text-align:center;font-size:15px;font-weight:bold;min-width:40px;padding:2px 2px;">Дата внесения</td>
                                        </tr>
                                        ' . $priority_info[$oper[$item]['equip_id']] . '
                                        <tr><td style="border-width:0px;padding:2px 2px;">&nbsp;</td></tr>
                                        <tr>
                                            <td style="text-align:left;font-size:15px;min-width:40px;padding:2px 2px;">Вид планирования:</td>
                                            <td colspan="9" style="text-align:left;font-size:15px;min-width:40px;padding:2px 2px;">' . $planOrNot . '</td>
                                        </tr>
                                        <tr>
                                            <td colspan="10" style="text-align:left;font-size:15px;min-width:40px;padding:2px 2px;">Объем мероприятий: ' . $oper_type_dict[$oper[$item]['op_type']] . $info . '</td>
                                        </tr>
                                        <tr>
                                            <td colspan="5" style="text-align:left;font-size:15px;min-width:40px;padding:2px 2px;">Комментарии по мероприятию: ' . $oper[$item]['comment'] . '</td>
                                            <td colspan="5" style="border-width:0px;padding:2px 2px;">&nbsp;</td>
                                        </tr>
                                        <tr><td style="border-width:0px;padding:2px 2px;">&nbsp;</td></tr>
                                        <tr>
                                            <td colspan="10" style="text-align:left;font-size:18px;min-width:40px;padding:2px 2px;">Параметры до ремонта</td>
                                        </tr>
                                        <tr style="border: 1px solid black">
                                            <td colspan="4" style="border: 1px solid black;text-align:left;font-size:15px;font-weight:bold;min-width:40px;padding:2px 2px;">Дата и время остановки</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;min-width:40px;padding:2px 2px;">'.substr($stop[$oper[$item]['equip_id']]['datetime'], 0, 10).'</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;min-width:40px;padding:2px 2px;">Время</td>
                                            <td colspan="4" style="text-align:center;font-size:15px;border: 1px solid black;min-width:40px;padding:2px 2px;">'.substr($stop[$oper[$item]['equip_id']]['datetime'], 11, 8).'</td>
                                        </tr>
                                        <tr style="border: 1px solid black">
                                            <td colspan="2" style="text-align:left;font-size:15px;border: 1px solid black;font-weight:bold;min-width:40px;padding:2px 2px;">Последний плановый ТОиР</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;min-width:40px;padding:2px 2px;">'.$operLast[$oper[$item]['equip_id']].'</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;min-width:40px;padding:2px 2px;">дата</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;min-width:40px;padding:2px 2px;">'.$maxDate[$oper[$item]['equip_id']].'</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;min-width:40px;padding:2px 2px;">Последний инцидент</td>
                                            <td colspan="4" style="text-align:center;font-size:15px;border: 1px solid black;min-width:40px;padding:2px 2px;">&nbsp;</td>
                                        </tr>
                                        <tr style="border: 1px solid black">
                                            <td style="text-align:left;font-size:15px;border: 1px solid black;font-weight:bold;min-width:40px;padding:2px 2px;">Наработка</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;min-width:40px;padding:2px 2px;">С начала экспл., ч</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;min-width:40px;padding:2px 2px;">' . $equip_params[$oper[$item]['equip_id']]['cno_narab']['valuestr'] . '</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;min-width:40px;padding:2px 2px;">После КР, ч</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;min-width:40px;padding:2px 2px;">' . $kr[$oper[$item]['equip_id']] . '</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;min-width:40px;padding:2px 2px;">После ТР, ч</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;min-width:40px;padding:2px 2px;">' . $tr[$oper[$item]['equip_id']] . '</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;min-width:40px;padding:2px 2px;">После ТО, ч</td>
                                            <td colspan="2" style="text-align:center;font-size:15px;border: 1px solid black;min-width:40px;padding:2px 2px;">' . $afterTo[$myKey] . '</td>
                                        </tr>
                                        <tr style="border: 1px solid black">
                                            <td style="text-align:left;font-size:15px;border: 1px solid black;font-weight:bold;min-width:40px;padding:2px 2px;">Аналитика</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;min-width:40px;padding:2px 2px;">СНО, ч</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;min-width:40px;padding:2px 2px;">&nbsp;</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;min-width:40px;padding:2px 2px;">МРП, ч</td>
                                            <td colspan="6" style="text-align:center;font-size:15px;border: 1px solid black;min-width:40px;padding:2px 2px;">
                                                    ' . (fill($equip_params[$oper[$item]['equip_id']]['cno_mrp']['valuestr']) ? $equip_params[$oper[$item]['equip_id']]['cno_mrp']['valuestr'] : '-') . '
                                            </td>
                                        </tr>
                                        <tr><td style="border-width:0px;padding:2px 2px;">&nbsp;</td></tr>
                                        <tr>
                                            <td colspan="10" style="text-align:left;font-size:18px;min-width:40px;padding:2px 2px;">Текущие эксплуатационные параметры</td>
                                        </tr>
                                        <tr style="border: 1px solid black">
                                            <td colspan="3" style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;min-width:40px;padding:2px 2px;">Параметр</td>
                                            <td colspan="2" style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;min-width:40px;padding:2px 2px;">Предыдущее значение</td>
                                            <td colspan="2" style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;min-width:40px;padding:2px 2px;">Текущее значение</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;min-width:40px;padding:2px 2px;">Ед. изм.</td>
                                            <td colspan="2" style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;min-width:40px;padding:2px 2px;">Дата, время замера</td>
                                        </tr>
                                        ' . $oper_control[$oper[$item]['equip_id']] . '
                                        <tr><td style="border-width:0px;padding:2px 2px;">&nbsp;</td></tr>
                                        <tr>
                                            <td colspan="10" style="text-align:left;font-size:18px;min-width:40px;padding:2px 2px;">Рисковые параметры</td>
                                        </tr>
                                        <tr style="border: 1px solid black">
                                            <td colspan="3" style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;min-width:40px;padding:2px 2px;">Параметр</td>
                                            <td colspan="2" style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;min-width:40px;padding:2px 2px;">Предыдущее значение</td>
                                            <td colspan="2" style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;min-width:40px;padding:2px 2px;">Текущее значение</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;min-width:40px;padding:2px 2px;">Ед. изм.</td>
                                            <td colspan="2" style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;min-width:40px;padding:2px 2px;">Дата, время замера</td>
                                        </tr>
                                        ' . $risk[$oper[$item]['equip_id']] . '
                                        <tr><td style="border-width:0px;padding:2px 2px;">&nbsp;</td></tr>
                                        <tr>
                                            <td colspan="10" style="text-align:left;font-size:18px;min-width:40px;padding:2px 2px;">Передача в ремонт: Заполняет эксплуатирующая служба</td>
                                        </tr>
                                        <tr style="border: 1px solid black">
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Оборудование<br>выведено из<br>технологической<br>схемы</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">Задвижками<br>или<br>заглушками</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Наряд-допуск<br>на<br>выполнения<br>ТОиР</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">Да/Нет</td>
                                            <td colspan="3" style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Электрическая схема<br>оборудования разобрана. С<br>датчиков КИПиА и кабелей эл.<br>питания демонтированы.</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">Да/Нет</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Оборудование<br>опорожено и<br>очищено</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">Да/Нет</td>
                                        </tr>
                                        <tr style="border: 1px solid black">
                                            <td colspan="10" style="text-align:left;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Выявленные нарушения в процессе эксплуатации:</td>
                                        </tr>
                                        <tr style="border: 1px solid black">
                                            <td colspan="10" style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                        </tr>
                                        <tr style="border: 1px solid black">
                                            <td colspan="10" style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                        </tr>
                                        <tr style="border: 1px solid black">
                                            <td style="text-align:left;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">В ТОиР<br>ПЕРЕДАЛ:</td>
                                            <td colspan="4" style="text-align:right;font-size:8px;border: 1px solid black;padding:2px 2px;"><br>ФИО, должность, дата, время, подпись</td>
                                            <td style="text-align:left;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">В ТОиР<br>ПРИНЯЛ:</td>
                                            <td colspan="4" style="text-align:right;font-size:8px;border: 1px solid black;padding:2px 2px;"><br>ФИО, должность, дата, время, подпись</td>
                                        </tr>
                                        <tr><td style="border-width:0px;padding:2px 2px;">&nbsp;</td></tr>
                                        <tr>
                                            <td colspan="10" style="text-align:left;font-size:18px;min-width:40px;padding:2px 2px;">Выполняемые мероприятия</td>
                                        </tr>
                                        <tr style="border: 1px solid black">
                                            <td colspan="3" rowspan="2" style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;min-width:40px;padding:2px 2px;">Операция</td>
                                            <td rowspan="2" style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;min-width:40px;padding:2px 2px;">Дисциплина</td>
                                            <td rowspan="2" style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Группа<br>операций<br>(резерв)</td>
                                            <td colspan="2" style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Трудозатраты, ч.</td>
                                            <td rowspan="2" style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;min-width:40px;padding:2px 2px;">Исполнитель</td>
                                            <td colspan="2" rowspan="2" style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;min-width:40px;padding:2px 2px;">Коментарий</td>
                                        </tr>
                                        <tr style="border: 1px solid black">
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;min-width:90px;padding:2px 2px;">План</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;min-width:90px;padding:2px 2px;">Факт</td>
                                        </tr>
                                        ' . $sub_oper[$oper[$item]['equip_id']][$oper[$item]['op_type']] . '
                                        <tr>
                                            <td colspan="10" style="text-align:left;font-size:15px;min-width:40px;padding:2px 2px;">Исполнители</td>
                                        </tr>
                                        <tr style="border: 1px solid black">
                                            <td colspan="2" style="border: 1px solid black;font-weight:bold;padding:2px 2px;">Дата</td>
                                            <td colspan="6" style="border: 1px solid black;font-weight:bold;padding:2px 2px;">ФИО</td>
                                            <td colspan="2" style="border: 1px solid black;font-weight:bold;padding:2px 2px;">Подпись</td>
                                        </tr>
                                        <tr style="border: 1px solid black">
                                            <td colspan="2" style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                            <td colspan="6" style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                            <td colspan="2" style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                        </tr>
                                        <tr style="border: 1px solid black">
                                            <td colspan="2" style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                            <td colspan="6" style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                            <td colspan="2" style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                        </tr>
                                        <tr style="border: 1px solid black">
                                            <td colspan="2" style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                            <td colspan="6" style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                            <td colspan="2" style="border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                        </tr>
                                        <tr><td style="border-width:0px;padding:2px 2px;">&nbsp;</td></tr>
                                        <tr>
                                            <td colspan="10" style="text-align:left;font-size:18px;min-width:40px;padding:2px 2px;">Использование ТМЦ</td>
                                        </tr>
                                        <tr style="border: 1px solid black">
                                            <td rowspan="2" style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;min-width:40px;padding:2px 2px;">Наименование ТМЦ</td>
                                            <td rowspan="2" style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;min-width:40px;padding:2px 2px;">Категория (ГСМ,<br>расходные, ЗЧ)</td>
                                            <td rowspan="2" style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;min-width:40px;padding:2px 2px;">Ед. изм.</td>
                                            <td rowspan="2" style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;min-width:40px;padding:2px 2px;">Каталожный<br>номер</td>
                                            <td rowspan="2" style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;min-width:40px;padding:2px 2px;">Код по ЕУС</td>
                                            <td colspan="2" style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Количество</td>
                                            <td rowspan="2" style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;min-width:40px;padding:2px 2px;">Исполнитель</td>
                                            <td colspan="2" rowspan="2" style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;min-width:40px;padding:2px 2px;">Коментарий</td>
                                        </tr>
                                        <tr style="border: 1px solid black">
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;min-width:10px;padding:2px 2px;">План</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;min-width:10px;padding:2px 2px;">Факт</td>
                                        </tr>
                                        ' . $zip[$oper[$item]['equip_id']][$oper[$item]['op_type']] . '
                                        <tr><td style="border-width:0px;padding:2px 2px;">&nbsp;</td></tr>';
                        if (in_array($obj_type_code[$oper[$myKey]['equip_id']],$obj_type_code_array)) {
                            $html .= '
                                <tr>
                                            <td colspan="10" style="text-align:left;font-size:18px;min-width:40px;padding:2px 2px;">Передача в эксплуатацию: Заполняет служба ТОИР</td>
                                        </tr>
                                        <tr style="border: 1px solid black">
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Оборудование<br>включено в тех.<br>процесс</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">Да/Нет</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Наряд-допуск<br>закрыт</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">Да/Нет</td>
                                            <td colspan="3" style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Испытания на<br>герметичность<br>выполнены, Рисп</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;"> </td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;"> </td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">Да/Нет</td>
                                        </tr>
                                        <tr style="border: 1px solid black">
                                            <td rowspan="6" style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;"> </td>
                                            <td colspan="4" style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">Показания замера вибрации до выполнения ТОиР<br>мм/сек</td>
                                            <td colspan="5" style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">Показания замера вибрации после выполнения ТОиР<br>мм/сек</td>
                                        </tr>
                                        <tr style="border: 1px solid black">
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Место замера</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Горизонталь</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Вертикаль</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Ось</td>
                                            <td colspan="2" style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Место замера</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Горизонталь</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Вертикаль</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Ось</td>
                                        </tr>
                                        <tr style="border: 1px solid black">
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">ППН</td>
                                            <td style="border-width:1px;padding:2px 2px;">&nbsp;</td>
                                            <td style="border-width:1px;padding:2px 2px;">&nbsp;</td>
                                            <td style="border-width:1px;padding:2px 2px;">&nbsp;</td>
                                            <td colspan="2" style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">ППН</td>
                                            <td style="border-width:1px;padding:2px 2px;">&nbsp;</td>
                                            <td style="border-width:1px;padding:2px 2px;">&nbsp;</td>
                                            <td style="border-width:1px;padding:2px 2px;">&nbsp;</td>
                                        </tr>
                                        <tr style="border: 1px solid black">
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">ЗПН</td>
                                            <td style="border-width:1px;padding:2px 2px;">&nbsp;</td>
                                            <td style="border-width:1px;padding:2px 2px;">&nbsp;</td>
                                            <td style="border-width:1px;padding:2px 2px;">&nbsp;</td>
                                            <td colspan="2" style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">ЗПН</td>
                                            <td style="border-width:1px;padding:2px 2px;">&nbsp;</td>
                                            <td style="border-width:1px;padding:2px 2px;">&nbsp;</td>
                                            <td style="border-width:1px;padding:2px 2px;">&nbsp;</td>
                                        </tr>
                                        <tr style="border: 1px solid black">
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">ППД</td>
                                            <td style="border-width:1px;padding:2px 2px;">&nbsp;</td>
                                            <td style="border-width:1px;padding:2px 2px;">&nbsp;</td>
                                            <td style="border-width:1px;padding:2px 2px;">&nbsp;</td>
                                            <td colspan="2" style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">ППД</td>
                                            <td style="border-width:1px;padding:2px 2px;">&nbsp;</td>
                                            <td style="border-width:1px;padding:2px 2px;">&nbsp;</td>
                                            <td style="border-width:1px;padding:2px 2px;">&nbsp;</td>
                                        </tr>
                                        <tr style="border: 1px solid black">
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">ЗПД</td>
                                            <td style="border-width:1px;padding:2px 2px;">&nbsp;</td>
                                            <td style="border-width:1px;padding:2px 2px;">&nbsp;</td>
                                            <td style="border-width:1px;padding:2px 2px;">&nbsp;</td>
                                            <td colspan="2" style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">ЗПД</td>
                                            <td style="border-width:1px;padding:2px 2px;">&nbsp;</td>
                                            <td style="border-width:1px;padding:2px 2px;">&nbsp;</td>
                                            <td style="border-width:1px;padding:2px 2px;">&nbsp;</td>
                                        </tr>
                                        <tr style="border: 1px solid black">
                                            <td colspan="5" style="text-align:left;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Заключение о годности или негодности эксплуатации (допустимый<br>уровень- мм/сек</td>
                                            <td colspan="5" style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                        </tr>
                                        <tr style="border: 1px solid black">
                                            <td style="text-align:left;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Из ТОиР<br>ПЕРЕДАЛ:</td>
                                            <td colspan="4" style="text-align:right;font-size:8px;border: 1px solid black;padding:2px 2px;"><br>ФИО, должность, дата, время, подпись</td>
                                            <td style="text-align:left;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">В эксплуатацию<br>ПРИНЯЛ:</td>
                                            <td colspan="4" style="text-align:right;font-size:8px;border: 1px solid black;padding:2px 2px;"><br>ФИО, должность, дата, время, подпись</td>
                                        </tr>
                                    </tbody>
                                </table>
                            <pagebreak>';
                        } else {
                            $html .= '<tr>
                                            <td colspan="10" style="text-align:left;font-size:18px;min-width:40px;padding:2px 2px;">Передача в эксплуатацию: Заполняет служба ТОИР</td>
                                        </tr>
                                        <tr style="border: 1px solid black">
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Оборудование<br>включено в тех.<br>процесс</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">Да/Нет</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Наряд-допуск<br>закрыт</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">Да/Нет</td>
                                            <td colspan="3" style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Испытания на<br>герметичность<br>выполнены, Рисп</td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;"> </td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;"> </td>
                                            <td style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">Да/Нет</td>
                                        </tr>
                                        <tr style="border: 1px solid black">
                                            <td colspan="2" style="text-align:left;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Параметры при рабочем испытании:</td>
                                            <td colspan="8" style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                        </tr>
                                        <tr style="border: 1px solid black">
                                            <td colspan="10" style="border-width:1px;padding:2px 2px;">&nbsp;</td>
                                        </tr>
                                        <tr style="border: 1px solid black">
                                            <td colspan="10" style="border-width:1px;padding:2px 2px;">&nbsp;</td>
                                        </tr>
                                        <tr style="border: 1px solid black">
                                            <td colspan="4" style="text-align:left;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Заключение о годности или негодности эксплуатации.</td>
                                            <td colspan="6" style="text-align:center;font-size:15px;border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                        </tr>
                                        <tr style="border: 1px solid black">
                                            <td colspan="10" style="border-width:1px;padding:2px 2px;">&nbsp;</td>
                                        </tr>
                                        <tr style="border: 1px solid black">
                                            <td colspan="10" style="border-width:1px;padding:2px 2px;">&nbsp;</td>
                                        </tr>
                                        <tr style="border: 1px solid black">
                                            <td style="text-align:left;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Из ТОиР<br>ПЕРЕДАЛ:</td>
                                            <td colspan="4" style="text-align:right;font-size:8px;border: 1px solid black;padding:2px 2px;"><br>ФИО, должность, дата, время, подпись</td>
                                            <td style="text-align:left;font-size:15px;border: 1px solid black;font-weight:bold;padding:2px 2px;">В эксплуатацию<br>ПРИНЯЛ:</td>
                                            <td colspan="4" style="text-align:right;font-size:8px;border: 1px solid black;padding:2px 2px;"><br>ФИО, должность, дата, время, подпись</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <pagebreak>';
                        }
                        if (fill($complex[$item])) {
                            $otvetPodpis = '';
                            for ($i = 1; $i <= 9; $i++) {
                                $text = '&nbsp;';
                                if ($i == 1) $text = 'Ответственный за объем и сроки выполнения работ';
                                elseif ($i == 4) $text = 'Ответственный за качественное выполнение работ';
                                elseif ($i == 7) $text = 'Ответственный за приемку работ и комплексное<br>закрытие';
                                $otvetPodpis .= '
                                    <tr style="border: 1px solid black">
                                        <td colspan="3" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">' . $text . '</td>
                                        <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                        <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                        <td colspan="3" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                    </tr>
                                ';
                            }
                            for ($c = 1; $c <= 3; $c++) {
                                $html .= '
                                    <table class="ws_report_table" style="text-align:center;table-layout:fixed;line-height: normal;">
                                        <tbody>
                                            <tr>
                                                <td colspan="5" rowspan="4" style="text-align:left;border-width:0px;padding-top:10px;padding-right:0px;padding-bottom:0px;padding-left:0px;">
                                                    <img width="220px" src="' . $image[$oper[$item]['equip_id']] . '">
                                                </td>
                                                <td colspan="5" style="text-align:right;font-size:12px;border-width:0px;padding-top:10px;padding-right:0px;padding-bottom:10px;padding-left:0px;">
                                                    Заказ-наряд № ' . date('Y', time()) . $item . '  от ' . date('d.m.Y', time()) . '
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="5" style="text-align:right;font-size:12px;border-width:0px;padding:2px 2px;">
                                                    для подразделения: ' . $service[$c] . '
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="5" style="text-align:right;font-size:12px;border-width:0px;padding:2px 2px;">
                                                    на выполнение работ: ' . $oper_type_dict[$oper[$item]['op_type']] . '
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="5" style="text-align:right;font-size:12px;border-width:0px;padding:2px 2px;">
                                                    на оборудовании: ' . (fill($equipInfo[$oper[$item]['equip_id']]['name']) ? $equipInfo[$oper[$item]['equip_id']]['name'] : '-') . '
                                                </td>
                                            </tr>
                                            <tr><td>&nbsp;</td></tr>
                                            <tr>
                                                <td colspan="10" style="text-align:left;font-size:16px;min-width:40px;padding:2px 2px;font-weight:bold;">1. Информация об оборудовании</td>
                                            </tr>
                                            <tr><td>&nbsp;</td></tr>
                                            <tr>
                                                <td colspan="2" style="text-align:left;font-size:14px;font-weight:bold;padding:2px 2px;">
                                                    Место установки
                                                </td>
                                                <td colspan="8" style="text-align:left;font-size:14px;padding:2px 2px;">
                                                    : ' . $mest_ustan[$oper[$item]['equip_id']] . '
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" style="text-align:left;font-size:14px;font-weight:bold;padding:2px 2px;">
                                                    Технологический номер
                                                </td>
                                                <td colspan="3" style="text-align:left;font-size:14px;padding:2px 2px;">
                                                    : ' . (fill($equip_params[$oper[$item]['equip_id']]['cno_tech_number']['valuestr']) ? $equip_params[$oper[$item]['equip_id']]['cno_tech_number']['valuestr'] : '-') . '
                                                </td>
                                                <td colspan="2" style="text-align:left;font-size:14px;font-weight:bold;padding:2px 2px;">
                                                    Таг №
                                                </td>
                                                <td colspan="3" style="text-align:left;font-size:14px;padding:2px 2px;">
                                                    : ' . (fill($equip_params[$oper[$item]['equip_id']]['cno_tag']['valuestr']) ? $equip_params[$oper[$item]['equip_id']]['cno_tag']['valuestr'] : '-') . '
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" style="text-align:left;font-size:14px;font-weight:bold;padding:2px 2px;">
                                                    Наименование типа
                                                </td>
                                                <td colspan="3" style="text-align:left;font-size:14px;padding:2px 2px;">
                                                    : ' . $names[$oper[$item]['equip_id']]['obj_type_name'] . '
                                                </td>
                                                <td colspan="2" style="text-align:left;font-size:14px;font-weight:bold;padding:2px 2px;">
                                                    Тип оборудования
                                                </td>
                                                <td colspan="3" style="text-align:left;font-size:14px;padding:2px 2px;">
                                                    : ' . (fill($equip_params[$oper[$item]['equip_id']]['cno_type_of_equip']['valuenum']) ? $equipType[$equip_params[$oper[$item]['equip_id']]['cno_type_of_equip']['valuenum']] : '-') . '
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" style="text-align:left;font-size:14px;font-weight:bold;padding:2px 2px;">
                                                    Марка
                                                </td>
                                                <td colspan="8" style="text-align:left;font-size:14px;padding:2px 2px;">
                                                    : ' . (fill($equipInfo[$oper[$item]['equip_id']]['name']) ? $equipInfo[$oper[$item]['equip_id']]['name'] : '-') . '
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" style="text-align:left;font-size:14px;font-weight:bold;padding:2px 2px;">
                                                    Завод-изготовитель
                                                </td>
                                                <td colspan="8" style="text-align:left;font-size:14px;padding:2px 2px;">
                                                    : ' . (fill($equip_params[$oper[$item]['equip_id']]['cno_zavod_izg']['valuestr']) ? $izg[$equip_params[$oper[$item]['equip_id']]['cno_zavod_izg']['valuestr']] : '-') . '
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" style="text-align:left;font-size:14px;font-weight:bold;padding:2px 2px;">
                                                    Категория оборудования
                                                </td>
                                                <td colspan="3" style="text-align:left;font-size:14px;padding:2px 2px;">
                                                    : ' . (fill($equip_params[$oper[$item]['equip_id']]['cno_vazhn_obor']['valuestr']) ? $vazhn[$equip_params[$oper[$item]['equip_id']]['cno_vazhn_obor']['valuestr']] : '-') . '
                                                </td>
                                                <td colspan="2" style="text-align:left;font-size:14px;font-weight:bold;padding:2px 2px;">
                                                    Статус
                                                </td>
                                                <td colspan="3" style="text-align:left;font-size:14px;padding:2px 2px;">
                                                    : ' . (fill($ceh[$equip_params[$oper[$item]['equip_id']]['cno_ceh']['valuestr']]['code']) == 'pnr' ? "ПНР" : 'Введено') . '
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" style="text-align:left;font-size:14px;font-weight:bold;padding:2px 2px;">
                                                    Состояние
                                                </td>
                                                <td colspan="8" style="text-align:left;font-size:14px;padding:2px 2px;">
                                                    : ' . (fill($equip_params[$oper[$item]['equip_id']]['cno_state']['valuestr']) ? $comboType[$equip_params[$oper[$item]['equip_id']]['cno_state']['valuestr']] : '-') . '
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" style="text-align:left;font-size:14px;font-weight:bold;padding:2px 2px;">
                                                    Заводской номер
                                                </td>
                                                <td colspan="8" style="text-align:left;font-size:14px;padding:2px 2px;">
                                                    : ' . (fill($equip_params[$oper[$item]['equip_id']]['cno_zav_number']['valuestr']) ? $equip_params[$oper[$item]['equip_id']]['cno_zav_number']['valuestr'] : '-') . '
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" style="text-align:left;font-size:14px;font-weight:bold;padding:2px 2px;">
                                                    Дата выпуска
                                                </td>
                                                <td colspan="3" style="text-align:left;font-size:14px;padding:2px 2px;">
                                                    : ' . (fill($equip_params[$oper[$item]['equip_id']]['cno_date_vipusk']['valuestr']) ? $equip_params[$oper[$item]['equip_id']]['cno_date_vipusk']['valuestr'] : '-') . '
                                                </td>
                                                <td colspan="2" style="text-align:left;font-size:14px;font-weight:bold;padding:2px 2px;">
                                                    Дата ввода в эксплуатацию
                                                </td>
                                                <td colspan="3" style="text-align:left;font-size:14px;padding:2px 2px;">
                                                    : ' . (fill($equip_params[$oper[$item]['equip_id']]['cno_date_vvoda']['valuestr']) ? $equip_params[$oper[$item]['equip_id']]['cno_date_vvoda']['valuestr'] : '-') . '
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" style="text-align:left;font-size:14px;font-weight:bold;padding:2px 2px;">
                                                    Срок службы по паспорту
                                                </td>
                                                <td colspan="3" style="text-align:left;font-size:14px;padding:2px 2px;">
                                                    :  ' . $pasp . '
                                                </td>
                                                <td colspan="2" style="text-align:left;font-size:14px;font-weight:bold;padding:2px 2px;">
                                                    Срок службы фактический
                                                </td>
                                                <td colspan="3" style="text-align:left;font-size14px;padding:2px 2px;color:' . $color . '">
                                                    : ' . $txtFactSlujb . '
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" style="text-align:left;font-size:14px;font-weight:bold;padding:2px 2px;">
                                                    Срок продления по ЭПБ
                                                </td>
                                                <td colspan="8" style="text-align:left;font-size:14px;padding:2px 2px;">
                                                    : ' . (fill($equip_params[$oper[$item]['equip_id']]['cno_srok_epb']['valuestr']) ? $equip_params[$oper[$item]['equip_id']]['cno_srok_epb']['valuestr'] : '-') . '
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" style="text-align:left;font-size:14px;font-weight:bold;padding:2px 2px;">
                                                    Вероятность отказа
                                                </td>
                                                <td style="text-align:left;font-size:14px;padding:2px 2px;">
                                                    : ' . (fill($risk_matr_DO[$oper[$item]['equip_id']]) ? $risk_matr_DO[$oper[$item]['equip_id']] . ' %' : '-') . '
                                                </td>
                                                <td style="text-align:center;font-size:14px;font-weight:bold;padding:2px 2px;">
                                                    на
                                                </td>
                                                <td style="text-align:left;font-size:14px;padding:2px 2px;">
                                                    ' . date("d.m.Y", time()) . '
                                                </td>
                                                <td style="text-align:center;font-size:14px;font-weight:bold;padding:2px 2px;">
                                                    параметр риска:
                                                </td>
                                                <td colspan="4" style="text-align:center;font-size:14px;border-width:0px;padding:2px 2px;">&nbsp;</td>
                                            </tr>
                                            <tr><td>&nbsp;</td></tr>
                                            <tr>
                                                <td colspan="10" style="text-align:left;font-size:16px;min-width:40px;padding:2px 2px;font-weight:bold;">2. Информация о работах по настоящему заказ-наряду</td>
                                            </tr>
                                            <tr><td>&nbsp;</td></tr>
                                            <tr>
                                                <td style="text-align:left;font-size:14px;min-width:40px;padding:2px 2px;">Вид планирования:</td>
                                                <td colspan="9" style="text-align:left;font-size:14px;min-width:40px;padding:2px 2px;">' . $planOrNot . '</td>
                                            </tr>
                                            <tr>
                                                <td colspan="10" style="text-align:left;font-size:14px;min-width:40px;padding:2px 2px;">Объем мероприятий: ' . $oper_type_dict[$oper[$item]['op_type']] . $info . '</td>
                                            </tr> 
                                            <tr><td>&nbsp;</td></tr>
                                            <tr>
                                                <td colspan="10" style="text-align:left;font-size:16px;min-width:40px;padding:2px 2px;font-weight:bold;">3. Дополнительная информация</td>
                                            </tr>
                                            <tr>
                                                <td colspan="10" style="text-align:left;font-size:10px;min-width:40px;padding:2px 2px;">Заполняется системой за 5-7 календарных дней до непосредственного начала выполнения работ</td>
                                            </tr>
                                            <tr style="border: 1px solid black">
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Категория информации</td>
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">№п/п</td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Информация</td>
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Заполнил</td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Начальник участка,УОиРЭ</td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Начальник участка ' . $service[$c] . '</td>
                                            </tr>    
                                            <tr style="border: 1px solid black">
                                                <td colspan="2" rowspan="3" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;">
                                                    1. Сведения о невыполненных/
                                                    незавершенных работах
                                                    предыдущего ТО оборудования
                                                    и его составных элементов.
                                                    Отметка о необходимости
                                                    проведения/завершения таких
                                                    работ в рамках ТО по
                                                    настоящему Заказ-наряду.
                                                </td>
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;">1.</td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                            </tr>
                                            <tr style="border: 1px solid black"> 
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;">2.</td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                            </tr>
                                            <tr style="border: 1px solid black">
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;">3.</td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                            </tr>   
                                            <tr style="border: 1px solid black">
                                                <td colspan="2" rowspan="3" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;">
                                                    2. Дополнительные плановые/
                                                    внеплановые ремонтные
                                                    работы, которые необходимо
                                                    провести на оборудовании
                                                    параллельно с проведением
                                                    ТО по настоящему Заказ-
                                                    наряду.
                                                </td>
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;">1.</td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                            </tr>
                                            <tr style="border: 1px solid black">
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;">2.</td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                            </tr>
                                            <tr style="border: 1px solid black">
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;">3.</td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                            </tr> 
                                            <tr><td>&nbsp;</td></tr>  
                                            <tr>
                                                <td colspan="10" style="text-align:left;font-size:16px;min-width:40px;padding:2px 2px;font-weight:bold;">4. Состояние оборудования до начала выполнения работ</td>
                                            </tr>
                                            <tr>
                                                <td colspan="10" style="text-align:left;font-size:10px;min-width:40px;padding:2px 2px;">Заполняется системой за 5-7 календарных дней до непосредственного начала выполнения работ</td>
                                            </tr> 
                                            <tr style="border: 1px solid black">
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Последний плановый ТОиР</td>
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;">' . $operLast[$oper[$item]['equip_id']] . '</td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Дата</td>
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;">' . $maxDate[$oper[$item]['equip_id']] . '</td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Последний инцидент</td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                            </tr> 
                                            <tr style="border: 1px solid black">
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Наработка</td>
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">С начала экспл., ч</td>
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;">' . $equip_params[$oper[$item]['equip_id']]['cno_narab']['valuestr'] . '</td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">С даты последнего ТО, ч</td>
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;">' . $afterTo[$myKey] . '</td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">С последей даты отбора проб, ч</td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;">' . $afterOil[$myKey] . '</td>
                                            </tr>   
                                            <tr><td>&nbsp;</td></tr>  
                                            <tr>
                                                <td colspan="10" style="text-align:left;font-size:16px;min-width:40px;padding:2px 2px;font-weight:bold;">5. Фактическое выполнение работ</td>
                                            </tr>
                                            <tr>
                                                <td colspan="10" style="text-align:left;font-size:10px;min-width:40px;padding:2px 2px;">Блок заполняется по факту выполнения работ непосредственно перед закрытием З-Н</td>
                                            </tr>  
                                            <tr style="border: 1px solid black">
                                                <td colspan="2" rowspan="2" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Операция</td>
                                                <td rowspan="2" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Дисциплина</td>
                                                <td colspan="2" rowspan="2" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Группа операций (резерв)</td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Трудозатраты, ч.</td>
                                                <td rowspan="2" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Исполнитель</td>
                                                <td rowspan="2" colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Комментарий</td>
                                            </tr> 
                                            <tr style="border: 1px solid black">
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">План</td>
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Факт</td>
                                            </tr> 
                                            <tr style="border: 1px solid black">
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;">Согласно утвержденной технологической карте</td>
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;">' . $discipline[$c] . '</td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                            </tr>      
                                            <tr><td>&nbsp;</td></tr> 
                                            <tr style="border: 1px solid black">
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Дата проведения ТО</td>
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Время остановки</td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Время передачи в ТО</td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Время передачи в эксплуатацию</td>
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Время запуска</td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Фактическая наработка</td>
                                            </tr> 
                                            <tr style="border: 1px solid black">
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                            </tr> 
                                            <tr><td>&nbsp;</td></tr>  
                                            <tr>
                                                <td colspan="10" style="text-align:left;font-size:16px;min-width:40px;padding:2px 2px;font-weight:bold;">6. Использование ТМЦ</td>
                                            </tr>
                                            <tr><td>&nbsp;</td></tr>  
                                            <tr style="border: 1px solid black">
                                                <td rowspan="2" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Наименование ТМЦ</td>
                                                <td rowspan="2" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Категория (ГСМ, расходные, ЗЧ)</td>
                                                <td rowspan="2" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Ед. изм.</td>
                                                <td rowspan="2" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Каталожный номер</td>
                                                <td rowspan="2" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Код по ЕУС</td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Количество</td>
                                                <td rowspan="2" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Исполнитель</td>
                                                <td rowspan="2" colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Комментарий</td>
                                            </tr> 
                                            <tr style="border: 1px solid black">
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">План</td>
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Факт</td>
                                            </tr> 
                                            <tr style="border: 1px solid black">
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;">Согласно утвержденной технологической карте</td>
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                            </tr> 
                                            <tr><td>&nbsp;</td></tr>  
                                            <tr>
                                                <td colspan="10" style="text-align:left;font-size:16px;min-width:40px;padding:2px 2px;font-weight:bold;">7. Сведения о невыполненных/незавершенных работах ТО, выполненного по настоящему Заказ-наряду</td>
                                            </tr>
                                            <tr><td>&nbsp;</td></tr> 
                                            <tr style="border: 1px solid black">
                                                <td colspan="3" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Наименование оборудования</td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Количество</td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Вид ТО</td>
                                                <td colspan="3" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Комментарий</td>
                                            </tr> 
                                            <tr style="border: 1px solid black">
                                                <td colspan="3" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;">&nbsp;</td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                                <td colspan="3" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;"></td>
                                            </tr> 
                                            <tr><td>&nbsp;</td></tr>  
                                            <tr>
                                                <td colspan="10" style="text-align:left;font-size:16px;min-width:40px;padding:2px 2px;font-weight:bold;">8. Подписи ответсвенных лиц</td>
                                            </tr>
                                            <tr><td>&nbsp;</td></tr> 
                                            <tr style="border: 1px solid black">
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Оборудование включено в тех. процесс</td>
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;">Да/Нет</td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Наряд-допуск закрыт</td>
                                                <td style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;">Да/Нет</td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Заключение о годности или не годности эксплуатации</td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;padding:2px 2px;">Да/Нет</td>
                                            </tr> 
                                            <tr><td>&nbsp;</td></tr><tr><td>&nbsp;</td></tr> <tr><td>&nbsp;</td></tr> <tr><td>&nbsp;</td></tr>  
                                            <tr style="border: 1px solid black">
                                                <td colspan="3" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;"></td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Должность</td>
                                                <td colspan="2" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">ФИО</td>
                                                <td colspan="3" style="text-align:center;font-size:12px;border: 1px solid black;font-weight:bold;padding:2px 2px;">Подпись</td>
                                            </tr> 
                                            ' . $otvetPodpis . '                     
                                        </tbody>
                                    </table>
                                <pagebreak>';
                            }
                        }
                    }
                }
            }
        }
        $html = substr($html,0,-11);
        return ['html' => $html];
    }

    function getPage($v)
    {
        $input = $this->loader->core->getSettingData()["sys"]["input"];
        $group_id = $input[2];
        $op_id_array = explode(",", $input[3]);
        $op_id_array = ws_arrayUnique($op_id_array);
        if (fill($group_id && $op_id_array)) {
            $html = $this->getHtml(['op_id_array' => $op_id_array])['html'];
            $mpdf = new \Mpdf\Mpdf();
            $mpdf->WriteHTML($html);
            $uploadPath = $this->loader->core->getSettingData()["sys"]["upload_docs"];
            $location = $uploadPath . $input[1] . '/' . $group_id . '/';
            if(!is_dir($location)) mkdir($location,0777,true);
            //$this->getLocation(['folder_name' => $group_id]);
            $date = date('d.m.Y');
//            $date = "31.05.2020";
            $filename = $date . '.pdf';
            $mpdf->Output($location . $filename, \Mpdf\Output\Destination::FILE);
        }
    }

    function getDateText($val) {
        if ($val%10 == 1) {
            $year = " год";
        } elseif ($val%10 > 4 || $val == 11 || $val == 12 || $val == 13 || $val == 14 || $val%10 == 0) {
            $year = " лет";
        } elseif ($val%10 > 1 && $val%10 < 5) {
            $year = " года";
        }
        return $year;
    }
}