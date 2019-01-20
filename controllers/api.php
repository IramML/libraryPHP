<?php
class Api extends Controller {
    function __construct(){
        parent::__construct();
        $this->view->json=array();
    }

    function render(){
        $this->view->render('api/index');
    }

    function books($param = null){
        $method = $param[0];

        if($method=="get"){
            //get books
            if(isset($_GET['token'])){
                if($this->model->validateToken($_GET['token'])) {
                //filter books by client
                $idClient=$this->model->getIdByToken($_GET['token']);

                $orders=$this->model->getOrdersByClientID($idClient);

                $purchasedBooks=array();
                foreach ($orders as $row){
                    $order=new Order();
                    $order=$row;
                    array_push($purchasedBooks, $order->bookCode);
                }
                $books=$this->model->getAllBooks();
                $this->view->json['code']=200;
                $this->view->json['books']=array();
                foreach ($books as $row){
                    $booksObject=new Book();
                    $booksObject= $row;
                    $book = array(
                        'id'=>$booksObject->code,
                        'title'=>$booksObject->title,
                        'pages'=>$booksObject->numPages,
                        'editorial'=>$booksObject->editorial,
                        'author'=>$booksObject->author,
                        'cost'=>$booksObject->cost,
                        'image'=>$booksObject->bImage,
                        'there_pdf'=>'',
                        'there_physical'=>''
                    );
                    if($this->model->existPDF($book['id']))$book['there_pdf']='yes';
                    else $book['there_pdf']='no';

                    if($this->model->existPhysical($book['id']))$book['there_physical']='yes';
                    else $book['there_physical']='no';
                    //don't add if is purchased
                    if(!$this->isPurchased($purchasedBooks, $book))
                        array_push($this->view->json['books'], $book);
                }
                }else
                    $this->error("Token is wrong");
            }else{
                //get all books
                $this->view->json['code']=200;
                $this->view->json['books']=array();
                $books=$this->model->getAllBooks();
                foreach ($books as $row){
                    $booksObject=new Book();
                    $booksObject= $row;
                    $book = array(
                        'id'=>$booksObject->code,
                        'title'=>$booksObject->title,
                        'pages'=>$booksObject->numPages,
                        'editorial'=>$booksObject->editorial,
                        'author'=>$booksObject->author,
                        'cost'=>$booksObject->cost,
                        'image'=>$booksObject->bImage
                    );
                    array_push($this->view->json['books'], $book);
                }
            }
        }else{
            $this->error('Error calling the API');
            return;  
        }

        if ($this->view->json==[] || $this->view->json['books']==[]){
            $this->error('There are no elements');
            return;
        }
        $this->view->render('api/index');
    }
    
    function clients($param = null){
        $method = $param[0];
        if($method=="login"){
            if(isset($_POST['email'])&& isset($_POST['password'])){
                $data=array(
                    'email'=>$_POST['email'],
                    'password'=>$_POST['password']
                );
                $this->view->json['code']=200;
            
                $hash=$this->model->getPasswordByEmail($data['email']);
                if(password_verify($data['password'], $hash)){
                    $ID=$this->model->getClientIdByEmail($data['email']);
                    $token=$this->model->getTokenByID($ID);
                    $this->view->json['token']=$token;
                }else{
                    $this->error('Password or email is wrong');
                    return;
                }
            }else{
            $this->error('The email and password must not be empty');
            return;
            }
        }else if($method=="currentUser"){
            if(isset($_GET['token'])){
                $ID=$this->model->getIdByToken($_GET['token']);
                $user=$this->model->getUserByID($ID);
                $imgAvatar=$this->model->getAvatarByIDUser($ID);
                if($imgAvatar!=null)
                    $user['image']=$imgAvatar;
                $user['code']="200";
                $this->view->json=$user;
            }else{
                $this->error('Token is wrong');
                return;
            }
        }else if($method=="avatar"){
            if($_POST['image']!="" && isset($_POST['token'])){
                $ID=$this->model->getIdByToken($_POST['token']);
                $image=$_POST['image'];

                $imageName=$ID.".png";
                $directoryImage="public/Images/Clients/";
                $fileImage=$directoryImage.$imageName;

                if(file_put_contents($fileImage, base64_decode($image))) {
                    $data=[
                        'id'=>$ID,
                        'image'=>$fileImage
                    ];
                    if($this->model->uploadAvatar($data)){
                        $this->view->json['code']=200;
                        $this->view->json['message']="Image uploaded successfully";
                    }else{
                        $this->error('Error when inserting data');
                        return;
                    }
                }else{
                    $this->error('Error when upload image');
                    return;
                }
            }else{
                $this->error('Error calling the API');
                return;
            }
        }else{
            $this->error('Error calling the API');
            return; 
        }
        if (($this->view->json==[] || empty($this->view->json['token'])) && 
                ($this->view->json==[] || empty($this->view->json['name']))&&
                ($this->view->json==[] || empty($this->view->json['message']))){
            $this->error('Error calling the API');
            return;
        }
        $this->view->render('api/index');
    }

    function domiciles($param = null){
        $method=$param[0];
    }

    function isPurchased($idBooks, $book){
        $isDuplicate=false;
        foreach ($idBooks as $idBook) {
            if($isDuplicate) break;
            if ($idBook == $book['id']) $isDuplicate = true;
        }
        return $isDuplicate;
    }

    function error($message){
        $this->view->json=array('code'=>404,'message' => $message);
        $this->view->render('api/index');
    }
}