<?php


namespace application\models;

use application\core\Model;
use application\core\View;
use application\lib\Email;


class Photo extends Model {

    public function uploadImage($fileUpload) {
        if (isset($fileUpload)) {
            $errors = array();
            $fileName = $fileUpload['name'];
            $fileSize = $fileUpload['size'];
            $fileTmpName = $fileUpload['tmp_name'];

            $fileType = explode('.',  $fileName);
            $fileExt = strtolower(end($fileType));

            $availableExtensions = array('jpeg', 'jpg', 'png');
            if (!in_array($fileExt, $availableExtensions)){
                $errors[] = "Invalid file format";
            }
            if($fileSize >5242880) {
                $errors[] = 'file > 5 mb';
            }

            if (empty($errors)) {
                $fileNameNew = uniqid('', true). '.' . $fileExt;
                $targetDir = 'public/images/gallery/' . $fileNameNew;

                $params = [
                    'image_id' =>null,
                    'user_id' => $_SESSION['account']['user_id'],
                    'login' => $_SESSION['account']['login'],
                    'path' => '/' . $targetDir,
                ];

                if (move_uploaded_file($fileTmpName, $targetDir)) {
                    $this->db->query('INSERT INTO gallery VALUES (:image_id, :user_id, :login, NOW(), :path)', $params);
                }

                echo 'success';
            } else {
                foreach ($errors as $val) {
                    echo $val;
                }
            }
        }
    }



    public function getPhoto($page = 1) {

        $numberUrl = explode('/', $_SERVER['REQUEST_URI']);
        $page = end($numberUrl);
        $offset = ($page - 1) * 5;

        $sql = 'SELECT * FROM gallery ORDER BY creation_date DESC LIMIT 5 OFFSET ' . $offset;

        $photo = $this->db->row($sql);
        $photo = $this->getCountLike($photo);
        $photo = $this->getComments($photo);
        return($photo);
    }

    public function pagination() {
        $numberUrl = explode('/', $_SERVER['REQUEST_URI']);
        $page = end($numberUrl);

        $sql = 'SELECT COUNT(*) FROM `gallery`';

        $countPhoto = $this->db->column($sql);
        $lastPage = ceil($countPhoto / 5);
        $infoPage = [
            'page' => $page,
            'lastPage' => $lastPage
        ];
        return $infoPage;
    }

    public function getPhotoThisUser() {

        $params = [
            'user_id' => $_SESSION['account']['user_id'],
        ];
        return $this->db->row('SELECT * FROM `gallery` WHERE user_id = :user_id ORDER BY creation_date DESC', $params);

    }

    public function checkLike($imageId) {
        $params = [
            'image_id' => $imageId,
        ];
        $data = $this->db->row('SELECT `user_id` FROM `likes` WHERE image_id = :image_id', $params);
        foreach ($data as $val) {
            if ($val['user_id'] == $_SESSION['account']['user_id']) {
                return false;
            }
        }
        return true;
    }

    public function addLike($imageId) {

        $params = [
            'image_id' => $imageId,
        ];
        if (!$this->db->row('SELECT * FROM `gallery` WHERE image_id = :image_id', $params)) {
            View::errorCode(404);
        }
        $params = [
            'like_id' => null,
            'user_id' => $_SESSION['account']['user_id'],
            'image_id' => $imageId,
        ];
        $this->db->query('INSERT INTO `likes` VALUES (:like_id, :user_id, :image_id)', $params);
    }

    public function deleteLike($imageId) {
        $params = [
            'image_id' => $imageId,
        ];
        $data = $this->db->row('SELECT `user_id`, `like_id` FROM `likes` WHERE image_id = :image_id', $params);
        foreach ($data as $val) {
            if ($val['user_id'] == $_SESSION['account']['user_id']) {
                $params = [
                    'like_id' => $val['like_id'],
                ];
                $this->db->query('DELETE FROM `likes` WHERE like_id = :like_id', $params);
            }
        }
    }

    public function getCountLike($photo) {

        foreach ($photo as &$val) {
            $params = [
                'image_id' => $val['image_id'],
            ];
            $data = $this->db->column('SELECT COUNT(*) FROM `likes` WHERE image_id = :image_id', $params);
            $val['like'] = $data;
        }
        return $photo;
    }

    public function addComment($imageId, $comment) {
        $params = [
            'image_id' => $imageId,
        ];
        if (!$this->db->row('SELECT * FROM `gallery` WHERE image_id = :image_id', $params)) {
            View::errorCode(404);
        }
        $comment = htmlspecialchars($comment);
        $params = [
            'comment_id' => null,
            'image_id' => $imageId,
            'user_id' => $_SESSION['account']['user_id'],
            'login' => $_SESSION['account']['login'],
            'comment' => $comment,
        ];
        $this->db->query('INSERT INTO `comments` VALUES (:comment_id, :image_id, :user_id, :login, :comment)', $params);

        if ($_SESSION['account']['notify']) {
            Email::sendMail($_SESSION['account']['email'], 'Comment', 'New comment on your photo');
        }


    }

    public function getComments($photo) {

        foreach ($photo as &$val) {
            $params = [
                'image_id' => $val['image_id'],
            ];
            $data = $this->db->row('SELECT * FROM `comments` WHERE image_id = :image_id', $params);

            $val['comment'] = $data;
        }
        return $photo;
    }

    public function deleteComment($commentId) {
        $params = [
            'comment_id' => $commentId,
        ];
        $this->db->query("DELETE FROM `comments` WHERE comment_id = :comment_id", $params);
    }

    //Remove likes and comments on the photo
    public function deletePhoto($path, $imageId) {
        $params = [

            'image_id' => $imageId,
        ];
        $this->db->query("DELETE FROM `likes` WHERE image_id = :image_id", $params);
        $this->db->query("DELETE FROM `comments` WHERE image_id = :image_id", $params);
        $params = [
            'path' => $path,
        ];
        $this->db->query("DELETE FROM `gallery` WHERE path = :path", $params);

        $path = trim($path, '/');
        if (file_exists($path)) {
            unlink($path);
        }
    }



    function applySticker($mainImagePath, $stickerNumber) {

        $stickerPath = 'public/images/frame/'. $stickerNumber .'.png';
        $params = [
            'image_id' =>null,
            'user_id' => $_SESSION['account']['user_id'],
            'login' => $_SESSION['account']['login'],
            'path' => '/' . $mainImagePath,
        ];
        $info = getimagesize($mainImagePath);
        switch ($info[2]) {
            case 1:
                $mainimage = imageCreateFromGif($mainImagePath);
                break;
            case 2:
                $mainimage = imageCreateFromJpeg($mainImagePath);
                break;
            case 3:
                $mainimage = imageCreateFromPng($mainImagePath);
                break;
        }

        $sticker = imagecreatefrompng($stickerPath);

        $imageWidth = $info[0];
        $imageHeight = $info[1];
        $stickerWidth = imagesx($sticker);
        $stickerHeight = imagesy($sticker);

        $resImage = imagecreatetruecolor($imageWidth, $imageHeight);
        imagecopyresampled($resImage, $mainimage, 0,0,0,0, $imageWidth, $imageHeight, $imageWidth, $imageHeight);

        if ($stickerNumber == 4) {
            imagefilter($resImage, IMG_FILTER_GRAYSCALE);
        } else {
            imagecopyresized($resImage, $sticker, 0, 0, 0, 0, $imageWidth, $imageHeight, $stickerWidth, $stickerHeight);
        }



        if (imagepng($resImage, $mainImagePath, 0)) {
            $this->db->query('INSERT INTO gallery VALUES (:image_id, :user_id, :login, NOW(), :path)', $params);
        }

        imagedestroy($mainimage);
        imagedestroy($resImage);
        imagedestroy($sticker);
    }

}