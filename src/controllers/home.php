<?php
class HomeAction{
    protected $ci;
    protected $path;
    protected $baseUri;
    protected $data;
    //Constructor
    public function __construct(Slim\Container $ci) {
        $this->ci = $ci;
        $this->path = array();
        // Get assets path
        $this->baseUri = str_replace($_SERVER['DOCUMENT_ROOT'], '', dirname($_SERVER['SCRIPT_FILENAME']));
        $this->data = array(
            'path' => $this->path,
            // 子資料夾的路徑
            'uri'  => array('base' => $this->baseUri),
            'webinfo' => $GLOBALS['webinfo']
        );
    }

    public function homepage($request, $response, $args) {
        // if(time() > strtotime("2016-10-07 19:59:58"))
        if($request->isPost()){
            $result = array('status' => 'end','result'=>'比赛已结束' );
            $response->getBody()->write(json_encode($result));
        }else{
            $response = $this->ci->view->render($response, "index.twig", $this->data);
        }

        return $response;
    }

    public function test($request, $response, $args) {
        $response = $this->ci->view->render($response, "question_test.twig", $this->data);
        return $response;
    }
    public function loginTest($request, $response, $args) {
        $result = array('status' => 'notstart', 'result'=>'' );
        $gets = $request->getParsedBody();
        if(isset($gets['name'])&& isset($gets['password'])){
            $arrData = array(
                ':name'=>$gets['name'],
                ':password'=>$gets['password'],
            );
            $sql = "SELECT sign FROM users WHERE name = :name AND password = :password";
            $prepare = $this->ci->db->prepare($sql);
            $prepare -> execute($arrData);
            $user = $prepare->fetch();
            if($user){
                $result['result'] = '登陆成功，但是比赛还没有开始，请稍后再来。';
            }else{
                $result['result'] = '姓名或者资格码不正确，请重试。';
            }
        }else{
            $result['result'] = '请输入姓名和资格码！';
        }
        $response->getBody()->write(json_encode($result));
    }

    public function login($request, $response, $args) {
        $result = array('status' => 'failed', 'result'=>'' );
        $gets = $request->getParsedBody();
        if(isset($gets['name'])&& isset($gets['password'])){
            $arrData = array(
                ':name'=>$gets['name'],
                ':password'=>$gets['password'],
            );
            $sql = "SELECT id,sign FROM users WHERE name = :name AND password = :password";
            $prepare = $this->ci->db->prepare($sql);
            $prepare -> execute($arrData);
            $user = $prepare->fetch();
            if($user){
                $questions = array();
                //The sql is not very good.
                $sql = "SELECT q.id as id,q.content AS question, q.type AS type, o.`option` AS `optionid`, o.content AS `option` FROM `questions` AS q, `options` AS o WHERE q.id = o.qid";
                $querys = $this->ci->db->query($sql)->fetchAll();
                foreach ($querys as $query) {
                    if(array_key_exists($query['id'],$questions)):
                        $questions[$query['id']]['options'][$query['optionid']] = $query['option'];
                    else:
                        $questions[$query['id']] = array(
                            "question" => $query['question'],
                            "type" => $query['type'],
                            "options" => array(
                                $query['optionid'] => $query['option']
                            )
                        );
                    endif;
                }
                $_SESSION['uid'] = $user['id'];
                $result['status'] = 'on';
                $result['questions'] = $questions;
            }else{
                $result['result'] = '姓名或者资格码不正确，请重试。';
            }
        }else{
            $result['result'] = '请输入姓名和资格码！';
        }
        $response->getBody()->write(json_encode($result));
        return $response;
    }

    public function exam($request, $response, $args) {
        $grade = 0;
        $answers = $request->getParsedBody();
        $sql = "SELECT id,correct FROM questions";
        $corrects_query = $this->ci->db->query($sql)->fetchAll();
        $corrects = array();
        $sign = '0';
        foreach($corrects_query as $correct_query){
            $corrects[$correct_query['id']] = $correct_query['correct'];
        }
        if(isset($_SESSION['uid'])){
            $sql = "SELECT starttime,sign FROM users WHERE id = :uid";
            $prepare = $this->ci->db->prepare($sql);
            $prepare -> execute(array(":uid"=>$_SESSION['uid']));
            $user = $prepare->fetch();
            if($user){
                $user["finishtime"] = date('Y-m-d H:i:s',time());
                $gap = (strtotime($user["finishtime"])-strtotime($user['starttime']));
                if($user["sign"] === '0' && $gap < 3720){
                    $sql = "INSERT INTO answers(qid,uid,answer,sign) VALUES(:qid,:uid,:answer,:sign)";
                    $prepare = $this->ci->db->prepare($sql);
                    foreach ($answers as $qid => $option) {
                        if($option == $corrects[$qid]){
                            $grade += 1;
                            $sign = '1';
                        }else{
                            $sign = '0';
                        }
                        $prepare -> execute(array(":qid"=>$qid,":uid"=>$_SESSION['uid'],":answer"=>$option,':sign'=>$sign));
                    }
                    $sql = "UPDATE users SET `point` = :grade,`finishtime` = :finishtime, sign = 1 WHERE id = :uid";
                    $prepare = $this->ci->db->prepare($sql);
                    $prepare -> execute(array(":grade"=>strval($grade),":finishtime"=>$user['finishtime'],":uid"=>$_SESSION['uid']));
                    unset($_SESSION['uid']);
                    $response->getBody()->write("考试结束，耗时".strval((int)($gap/60))."分".strval($gap%60)."秒，祝您取得好成绩！");
                }else{
                    if($user["sign"]=== '0' ){
                        $sql = "UPDATE users SET `sign` = -1 WHERE id = :uid";
                        $prepare = $this->ci->db->prepare($sql);
                        $prepare -> execute(array());
                        $response->getBody()->write("您已超时，本次成绩作废！");
                    }
                    $response->getBody()->write("您已经答过题目了。");
                }
            }else{
                $response->getBody()->write("出现未知错误，请重写填写信息，要是再次发生请联系管理员。");
            }
        }else{
            $response->getBody()->write("请填写个人信息！");
        }
        return $response;
    }
}

?>
