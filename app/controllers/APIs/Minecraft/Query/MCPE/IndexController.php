<?php

namespace GameAPIs\Controllers\APIs\Minecraft\Query\MCPE;

use GameAPIs\Libraries\Minecraft\Query\MCQuery;
use Redis;

class IndexController extends ControllerBase {

    public function initialize() {
        $this->tag->setTitle("GameAPIs");
    }

    public function indexAction() {
        $params = $this->dispatcher->getParams();
        if(empty($params['ip'])) {
            $output['error'] = "Please provide an address";
            echo json_encode($output, JSON_PRETTY_PRINT);
        } else {
            if(strpos($params['ip'],',')) {
                if(count(explode(',', $params['ip'])) > 5) {
                    $output['error'] = "Maximum address count surpassed. Please lower to 5 addresses.";
                    echo json_encode($output, JSON_PRETTY_PRINT);
                } else {
                    $this->dispatcher->forward(
                        [
                            "namespace"     => "GameAPIs\Controllers\APIs\Minecraft\Query\MCPE",
                            "controller"    => "index",
                            "action"        => "multi"
                        ]
                    );
                }
            } else {
                $this->dispatcher->forward(
                    [
                        "namespace"     => "GameAPIs\Controllers\APIs\Minecraft\Query\MCPE",
                        "controller"    => "index",
                        "action"        => "single"
                    ]
                );
            }
        }
    }

    public function singleAction() {
        $params = $this->dispatcher->getParams();
        if(strpos($params['ip'], ':')) {
            $explodeParams = explode(':', $params['ip']);
            $params['ip'] = $explodeParams[0];
            $params['port'] = $explodeParams[1];
        } else {
            $params['port'] = 19132;
        }
        $redis = new Redis();
        $redis->pconnect('/var/run/redis/redis.sock');
        if($redis->exists('query:'.$params['ip'].':'.$params['port'])) {
            $response = json_decode(base64_decode($redis->get('query:'.$params['ip'].':'.$params['port'])),true);
            if(!$response['online']) {
                $output['status']            = $response['online'];
                $output['hostname']          = $response['hostname'];
                $output['port']              = $params['port'];
                $output['error']             = $response['error'];
            } else {
                $output['status']            = $response['online'];
                $output['hostname']          = $response['hostname'];
                $output['port']              = $response['port'];
                $output['version']           = $response['version'];
                $output['software']          = $response['software'];
                $output['game_type']         = $response['game_type'];
                $output['game_name']         = $response['game_name'];
                $output['motd']              = $response['motd'];
                $output['htmlmotd']          = $response['htmlmotd'];
                $output['motds']['ingame']   = $response['motd'];
                $output['motds']['html']     = $response['htmlmotd'];
                $output['motds']['clean']    = $response['cleanmotd'];
                $output['map']               = $response['map'];
                $output['players']['online'] = $response['players'];
                $output['players']['max']    = $response['max_players'];
                $output['list']              = $response['player_list'];
                $output['plugins']           = $response['plugins'];
            }
            $output['cached'] = true;
        } else {
            $status                = new MCQuery();
            $getStatus             = $status->GetStatus($params['ip'], $params['port']);
            $response              = $getStatus->Response();
            $response['htmlmotd']  = $getStatus->MotdToHtml($response['motd']);
            $response['cleanmotd'] = $getStatus->ClearMotd($response['motd']);

            if(!$response['online']) {
                $output['status']               = $response['online'];
                $output['hostname']             = $response['hostname'];
                $output['port']                 = $params['port'];
                $output['error']                = $response['error'];
            } else {
                $output['status']               = $response['online'];
                $output['hostname']             = $response['hostname'];
                $output['port']                 = $response['port'];
                $output['version']              = $response['version'];
                $output['software']             = $response['software'];
                $output['game_type']            = $response['game_type'];
                $output['game_name']            = $response['game_name'];
                $output['motd']                 = $response['motd'];
                $output['htmlmotd']             = $response['htmlmotd'];
                $output['motds']['ingame']      = $response['motd'];
                $output['motds']['html']        = $response['htmlmotd'];
                $output['motds']['clean']       = $response['cleanmotd'];
                $output['map']                  = $response['map'];
                $output['players']['online']    = $response['players'];
                $output['players']['max']       = $response['max_players'];
                $output['list']                 = $response['player_list'];
                $output['plugins']              = $response['plugins'];
            }
            $output['cached'] = false;
            $redis->set('query:'.$params['ip'].':'.$params['port'], base64_encode(json_encode($response, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)), 15);
        }
        echo json_encode($output, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    }

    public function multiAction() {
        $params = $this->dispatcher->getParams();
        $explodeComma = explode(',', $params['ip']);
        unset($params['ip']);
        $i=0;
        $redis = new Redis();
        $redis->pconnect('/var/run/redis/redis.sock');
        foreach ($explodeComma as $key => $value) {
            if(strpos($value, ':')) {
                $explodeParams = explode(':', $value);
                $params['addresses'][$i]['ip'] = $explodeParams[0];
                $params['addresses'][$i]['port'] = $explodeParams[1];
            } else {
                $params['addresses'][$i]['ip'] = $value;
                $params['addresses'][$i]['port'] = 19132;
            }
            $i++;
        }
        foreach ($params['addresses'] as $key => $value) {
            $combined = $value['ip'].':'.$value['port'];
            if($redis->exists('query:'.$combined)) {
                $response = json_decode(base64_decode($redis->get('query:'.$combined)),true);
                if(!$response['online']) {
                    $output[$combined]['status']               = $response['online'];
                    $output[$combined]['hostname']             = $response['hostname'];
                    $output[$combined]['port']                 = $params['port'];
                    $output[$combined]['error']                = $response['error'];
                } else {
                    $output[$combined]['status']               = $response['online'];
                    $output[$combined]['hostname']             = $response['hostname'];
                    $output[$combined]['port']                 = $response['port'];
                    $output[$combined]['version']              = $response['version'];
                    $output[$combined]['software']             = $response['software'];
                    $output[$combined]['game_type']            = $response['game_type'];
                    $output[$combined]['game_name']            = $response['game_name'];
                    $output[$combined]['motd']                 = $response['motd'];
                    $output[$combined]['htmlmotd']             = $response['htmlmotd'];
                    $output[$combined]['motds']['ingame']      = $response['motd'];
                    $output[$combined]['motds']['html']        = $response['htmlmotd'];
                    $output[$combined]['motds']['clean']       = $response['cleanmotd'];
                    $output[$combined]['map']                  = $response['map'];
                    $output[$combined]['players']['online']    = $response['players'];
                    $output[$combined]['players']['max']       = $response['max_players'];
                    $output[$combined]['list']                 = $response['player_list'];
                    $output[$combined]['plugins']              = $response['plugins'];
                }
                $output[$combined]['cached'] = true;
            } else {
                $status                = new MCQuery();
                $getStatus             = $status->GetStatus($value['ip'], $value['port']);
                $response              = $getStatus->Response();
                $response['htmlmotd']  = $getStatus->MotdToHtml($response['motd']);
                $response['cleanmotd'] = $getStatus->ClearMotd($response['motd']);

                if(!$response['online']) {
                    $output[$combined]['status']               = $response['online'];
                    $output[$combined]['hostname']             = $response['hostname'];
                    $output[$combined]['port']                 = $params['port'];
                    $output[$combined]['error']                = $response['error'];
                } else {
                    $output[$combined]['status']               = $response['online'];
                    $output[$combined]['hostname']             = $response['hostname'];
                    $output[$combined]['port']                 = $response['port'];
                    $output[$combined]['version']              = $response['version'];
                    $output[$combined]['software']             = $response['software'];
                    $output[$combined]['game_type']            = $response['game_type'];
                    $output[$combined]['game_name']            = $response['game_name'];
                    $output[$combined]['motd']                 = $response['motd'];
                    $output[$combined]['htmlmotd']             = $response['htmlmotd'];
                    $output[$combined]['motds']['ingame']      = $response['motd'];
                    $output[$combined]['motds']['html']        = $response['htmlmotd'];
                    $output[$combined]['motds']['clean']       = $response['cleanmotd'];
                    $output[$combined]['map']                  = $response['map'];
                    $output[$combined]['players']['online']    = $response['players'];
                    $output[$combined]['players']['max']       = $response['max_players'];
                    $output[$combined]['list']                 = $response['player_list'];
                    $output[$combined]['plugins']              = $response['plugins'];
                }
                $output[$combined]['cached'] = false;
                $redis->set('query:'.$combined, base64_encode(json_encode($response, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)), 15);
            }
        }
        echo json_encode($output, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    }
}
