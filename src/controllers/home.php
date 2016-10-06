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
        $response = $this->ci->view->render($response, "index.twig", $this->data);
        return $response;
    }

    public function test($request, $response, $args) {
        $response = $this->ci->view->render($response, "question_test.twig", $this->data);
        return $response;
    }

    public function register($request, $response, $args) {
        // var_dump($request->getParsedBody());
        $ret = false;
        $gets = $request->getParsedBody();
        if(isset($gets['name'])&& isset($gets['college'])&& isset($gets['major'])&& isset($gets['tel'])):
            $arrData = array(
                ':name'=>$gets['name'],
                ':college'=>$gets['college'],
                ':major'=>$gets['major'],
                ':tel'=>$gets['tel'],
                ':regtime'=>date('Y-m-d H:i:s',time()),
                ':starttime'=>date('Y-m-d H:i:s',time())
            );
            $sql = "INSERT INTO users (name,college,major,tel,regtime,starttime) VALUES(:name,:college,:major,:tel,:regtime,:starttime)";
            $prepare = $this->ci->db->prepare($sql);
            $prepare->execute($arrData);
            $ret = $this->ci->db->lastInsertId();
        endif;

        if($ret):
            $questions = array();
            //The sql is not very good.
            $sql = "SELECT q.id as id,q.content AS question, q.type AS type, o.`option` AS `optionid`, o.content AS `option` FROM `questions` AS q, `options` AS o WHERE q.id = o.qid";
            $results = $this->ci->db->query($sql)->fetchAll();
            foreach ($results as $result) {
                if(array_key_exists($result['id'],$questions)):
                    $questions[$result['id']]['options'][$result['optionid']] = $result['option'];
                else:
                    $questions[$result['id']] = array(
                        "question" => $result['question'],
                        "type" => $result['type'],
                        "options" => array(
                            $result['optionid'] => $result['option']
                        )
                    );
                endif;
            }
            $_SESSION['uid'] = $ret;
            setcookie('PHPSESSID', session_id(), time() +4800);
            $response->getBody()->write(json_encode($questions));
        else:
            $error = "出现未知错误，请稍后重试或者联系管理员。";
            $response->getBody()->write("<script>alert(\"$error\")</script>");
        endif;

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
