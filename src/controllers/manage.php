<?php
class ManageAction{
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
            'webinfo' => $GLOBALS['webinfo'],
            'active_column' => 'report'
        );
    }
    public function login($request, $response, $args) {
        $gets = $request->getParsedBody();
        if(isset($gets['username']) && isset($gets['password'])){
            $sql = "SELECT password FROM users WHERE name=:username AND password=:password AND sign=2";
            $prepare = $this->ci->db->prepare($sql);
            $prepare -> execute(array(":username"=>$gets['username'],":password"=>$gets['password']));
            $admin = $prepare->fetch();
            if($admin){
                $_SESSION["admin"] = true;
                $response->withJson(array('status' => 'success','result'=>'登陆成功！'));
            }else{
                $response->withJson(array('status' => 'failure','result'=>'密码不正确或用户不存在！'));
            }
        }else{
            $response->withJson(array('status' => 'failure','result'=>'请输入用户名和密码！'));
        }
        return $response;
    }

    public function logout($request, $response, $args) {
        if(isset($_SESSION['admin']))
        {
            unset($_SESSION['admin']);
        }
        header("Location: ../manage");
        $response->withStatus(200)->withHeader('Location', $this->data['uri']['base'].'/manage');
        return $response;
    }

    public function report($request, $response, $args) {
        if(isset($_SESSION['admin'])){
            $this->data['active_column']='report';
            $sql = "SELECT `id`, `number`, `name`, `password`, `college`, `email`, `tel`, `regtime`, `starttime`, `finishtime`, `point`, `sign`,TO_SECONDS(finishtime) -TO_SECONDS(starttime) AS spenttime FROM users WHERE sign!=2  ORDER BY `point` DESC, spenttime ASC";
            $reports = $this->ci->db->query($sql)->fetchAll();
            $this->data['reports'] = $reports;
            $response = $this->ci->view->render($response, "manage/report.twig", $this->data);
        }else {
            $response = $this->ci->view->render($response, "manage/login.twig", $this->data);
        }
        return $response;
    }

    public function reportDetail($request, $response, $args) {
        if(isset($_SESSION['admin'])){
            $route = $request->getAttribute('route');
            $this->data['active_column']='report';
            $sql = "SELECT `id`, `number`, `name`, `password`, `college`, `email`, `tel`, `regtime`, `starttime`, `finishtime`, `point`, `sign`,TO_SECONDS(finishtime) -TO_SECONDS(starttime) AS spenttime FROM users WHERE id = :id";
            $prepare = $this->ci->db->prepare($sql);
            $prepare -> execute(array(":id"=>$route->getArgument('id')));
            $user = $prepare->fetch();
            $this->data['user'] = $user;

            $sql = "SELECT qid,answer,sign FROM answers WHERE uid=:uid";
            $prepare = $this->ci->db->prepare($sql);
            $prepare -> execute(array(":uid"=>$user['id']));
            $answers = $prepare->fetchALL();
            $this->data['answers'] = $answers;
            $response = $this->ci->view->render($response, "manage/report_detail.twig", $this->data);
        }else {
            $response = $this->ci->view->render($response, "manage/login.twig", $this->data);
        }
        return $response;
    }
}

?>
