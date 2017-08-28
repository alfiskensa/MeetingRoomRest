<?php

class DbOperation
{
    private $con;

    function __construct()
    {
        require_once dirname(__FILE__) . '/DbConnect.php';
        $db = new DbConnect();
        $this->con = $db->connect();
    }

    //Method to create a new user
    function registerUser($name, $email, $pass, $jabatan)
    {
        if (!$this->isUserExist($email)) {
            $password = md5($pass);
            $stmt = $this->con->prepare("INSERT INTO user (nama, email, password, jabatan) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $password, $jabatan);
            if ($stmt->execute())
                return USER_CREATED;
            return USER_CREATION_FAILED;
        }
        return USER_EXIST;
    }

    //Method for user login
    function userLogin($email, $pass)
    {
        $password = md5($pass);
        $stmt = $this->con->prepare("SELECT id_user FROM user WHERE email = ? AND password = ?");
        $stmt->bind_param("ss", $email, $password);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }

    //Method to send a message to another user
    function doBooking($id, $room, $date_used, $ket)
    {
        if(!$this->isRoomAvailable($date_used)){
		$stmt = $this->con->prepare("INSERT INTO booking (id_user, kd_ruangan, tgl_penggunaan, keterangan) VALUES (?, ?, ?, ?);");
        $stmt->bind_param("iiss", $id, $room, $date_used, $ket);
        if ($stmt->execute())
            return true;
        return false;
		}
		return ROOM_NOT_AVAILABLE;
    }

    //Method to update profile of user
    function updateProfile($id, $name, $email,  $gender)
    {
        $stmt = $this->con->prepare("UPDATE users SET nama = ?, email = ?, jabatan = ? WHERE id_user = ?");
        $stmt->bind_param("sssi", $name, $email,  $gender, $id);
        if ($stmt->execute())
            return true;
        return false;
    }
	
	//Method to update password of user
    function updatePassword($id, $pass)
    {
        $password = md5($pass);
		$stmt = $this->con->prepare("UPDATE users SET password = ? WHERE id_user = ?");
        $stmt->bind_param("si", $password, $id);
        if ($stmt->execute())
            return true;
        return false;
    }

    //Method to get messages of a particular user
    function getMessages($userid)
    {
        $stmt = $this->con->prepare("SELECT messages.id, (SELECT users.name FROM users WHERE users.id = messages.from_users_id) as `from`, (SELECT users.name FROM users WHERE users.id = messages.to_users_id) as `to`, messages.title, messages.message, messages.sentat FROM messages WHERE messages.to_users_id = ? ORDER BY messages.sentat DESC;");
        $stmt->bind_param("i", $userid);
        $stmt->execute();
        $stmt->bind_result($id, $from, $to, $title, $message, $sent);

        $messages = array();

        while ($stmt->fetch()) {
            $temp = array();

            $temp['id'] = $id;
            $temp['from'] = $from;
            $temp['to'] = $to;
            $temp['title'] = $title;
            $temp['message'] = $message;
            $temp['sent'] = $sent;

            array_push($messages, $temp);
        }

        return $messages;
    }

    //Method to get user by email
    function getUserByEmail($email)
    {
        $stmt = $this->con->prepare("SELECT id_user, nama, email, jabatan FROM user WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($id, $name, $email, $jabatan);
        $stmt->fetch();
        $user = array();
        $user['id_user'] = $id;
        $user['name'] = $name;
        $user['email'] = $email;
        $user['jabatan'] = $jabatan;
        return $user;
    }

    //Method to get all rooms
    function getAllRooms(){
        $stmt = $this->con->prepare("SELECT kd_ruangan, nama_ruangan, kapasitas FROM ruangan");
        $stmt->execute();
        $stmt->bind_result($kd_ruangan, $nama_ruangan, $kapasitas);
        $rooms = array();
        while($stmt->fetch()){
            $temp = array();
            $temp['kd_ruangan'] = $kd_ruangan;
            $temp['nama_ruangan'] = $nama_ruangan;
            $temp['kapasitas'] = $kapasitas;
            array_push($rooms, $temp);
        }
        return $rooms;
    }

    //Method to check if email already exist
    function isUserExist($email)
    {
        $stmt = $this->con->prepare("SELECT id_user FROM user WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }
	
	//Method to check if email already exist
    function isRoomAvailable($date)
    {
        $stmt = $this->con->prepare("SELECT id_booking FROM booking WHERE tgl_penggunaan = ?");
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }
	
	//Method to check if email already exist
    function isPassword($pass)
    {
        $password = md5($pass);
		$stmt = $this->con->prepare("SELECT id_user FROM user WHERE password = ?");
        $stmt->bind_param("s", $password);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }
}