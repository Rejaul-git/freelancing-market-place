 <?php
    // create_user.php
    // header("Content-Type: application/json");
    // header("Access-Control-Allow-Origin: *"); // React থেকে Access করতে
    // header("Access-Control-Allow-Methods: POST");
    // header("Access-Control-Allow-Headers: Content-Type");

    // require_once '../config/db.php'; // PDO কানেকশন

    // // React থেকে JSON আসবে
    // $data = json_decode(file_get_contents("php://input"));

    // // চেক করো সব ফিল্ড এসেছে কিনা
    // if (
    //     !isset($data->username) ||
    //     !isset($data->email) ||
    //     !isset($data->password) ||
    //     !isset($data->country)
    // ) {
    //     echo json_encode(["status" => "error", "message" => "Required fields missing"]);
    //     exit;
    // }

    // // ইনপুট ক্লিন করা
    // $username = htmlspecialchars(trim($data->username));
    // $email = htmlspecialchars(trim($data->email));
    // $password = $data->password;
    // $role = htmlspecialchars(trim($data->role));
    // $country = htmlspecialchars(trim($data->country));

    // // Optional fields
    // $img = isset($data->img) ? htmlspecialchars(trim($data->img)) : null;
    // $phone = isset($data->phone) ? htmlspecialchars(trim($data->phone)) : null;
    // $des = isset($data->des) ? htmlspecialchars(trim($data->des)) : null;

    // // Password hash করা
    // $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // try {
    //     $stmt = $pdo->prepare("INSERT INTO users (username, email, password, img, country, phone, des, role) 
    //                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    //     $stmt->execute([
    //         $username,
    //         $email,
    //         $passwordHash,
    //         $img,
    //         $country,
    //         $phone,
    //         $des,
    //         $role
    //     ]);

    //     echo json_encode(["status" => "success", "message" => "User created successfully"]);
    // } catch (PDOException $e) {
    //     echo json_encode(["status" => "error", "message" => "DB Error: " . $e->getMessage()]);
    // }

    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");
    header("Access-Control-Allow-Headers: Content-Type");

    require_once '../config/db.php';



    $response = [];

    try {
        // File Upload
        $imgPath = null;
        if (isset($_FILES['img']) && $_FILES['img']['error'] === 0) {
            $uploadDir = "../uploads/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $imgName = time() . "_" . basename($_FILES["img"]["name"]);
            $imgPath = $uploadDir . $imgName;
            if (!move_uploaded_file($_FILES["img"]["tmp_name"], $imgPath)) {
                throw new Exception("Image upload failed");
            }
        }

        // Input data
        $username = htmlspecialchars(trim($_POST['username']));
        $email = htmlspecialchars(trim($_POST['email']));
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $country = htmlspecialchars(trim($_POST['country']));
        $role = htmlspecialchars(trim($_POST['role']));
        $phone = isset($_POST['phone']) ? htmlspecialchars(trim($_POST['phone'])) : null;
        $des = isset($_POST['des']) ? htmlspecialchars(trim($_POST['des'])) : null;
        $img = $imgPath ? basename($imgPath) : null;

        if (!$username || !$email || !$password || !$country || !$role) {
            echo json_encode(["status" => "error", "message" => "All fields are required"]);
            exit;
        }

        // DB Insert
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, img, country, phone, des, role)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $email, $password, $img, $country, $phone, $des, $role]);

        echo json_encode(["status" => "success", "message" => "User created"]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
