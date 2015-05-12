<?php

namespace Blog\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\Session;

class DefaultController extends Controller
{
    public function indexAction()
    {
        $db = self::connect();

        $query = "SELECT * FROM posts ORDER BY date DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $posts = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $this->render('BlogMainBundle:Default:index.html.twig', array('posts' => $posts));
    }
    
    public function adminAction()
    {
        if(!self::authenticated())
            return $this->redirectToRoute('login_page');
        
        $request = $this->getRequest();
        $method = $request->getMethod();
        if($method === "POST") {
            $db = self::connect();
            $title = $request->get('title');
            $content = $request->get('content');
            $contnet = htmlentities($content);
            
            $query = "INSERT INTO posts (`title`, `content`) VALUES (:title, :content)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":title", $title);
            $stmt->bindParam(":content", $content);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                return $this->render('BlogMainBundle:Default:admin.html.twig', array('status' => "Successfully posted to blog!"));
            }
            else {
                return $this->render('BlogMainBundle:Default:admin.html.twig', array('status' => "Error posting to main feed."));
            }
        }
        return $this->render('BlogMainBundle:Default:admin.html.twig');
    }
    
    public function loginAction()
    {
        $request = $this->getRequest();
        $method = $request->getMethod();
        if($method === "POST") {
            $username = $request->get('username');
            $password = $request->get('password');
            if(self::verify($username, $password)) {
                return $this->redirectToRoute('admin_page');
            }
            else
                return $this->render('BlogMainBundle:Default:login.html.twig', array('error' => "Incorrect username or password"));
        }
        else {
            return $this->render('BlogMainBundle:Default:login.html.twig');
        }
    }
    
    public function verify($username, $password)
    {
        $session = $this->container->get('session');
        $db = self::connect();
        
        $query = "SELECT password FROM admins WHERE username=:user";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":user", $username);
        $stmt->execute();
        $hash = $stmt->fetchColumn();
        
        if(password_verify($password, $hash)) {
            $session->set('authorized', true);
            return true;
        }
        return false;
    }
    
    public function authenticated()
    {
        $session = $this->container->get('session');
        return $session->get('authorized', false);
    }
    
    private function connect()
    {
        return new \PDO("mysql:host=localhost;dbname=Blog", "root", '');
    }
}
