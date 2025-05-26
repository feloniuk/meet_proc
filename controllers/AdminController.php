<?php
class AdminController {
    private $userModel;
    private $rawMaterialModel;
    private $recipeModel;
    private $productModel;
    private $inventoryModel;
    private $orderModel;
    private $productionModel;
    private $videoSurveillanceModel;
    
    public function __construct() {
        // Перевірка на авторизацію та роль
        if (!Auth::isLoggedIn() || !Auth::hasRole('admin')) {
            Util::redirect(BASE_URL . '/home');
        }
        
        $this->userModel = new User();
        $this->rawMaterialModel = new RawMaterial();
        $this->recipeModel = new Recipe();
        $this->productModel = new Product();
        $this->inventoryModel = new Inventory();
        $this->orderModel = new Order();
        $this->productionModel = new Production();
        $this->videoSurveillanceModel = new VideoSurveillance();
    }
    
    // Управління користувачами
    public function users() {
        $data = [
            'title' => 'Управління користувачами',
            'users' => $this->userModel->getAll()
        ];
        
        require VIEWS_PATH . '/admin/users.php';
    }
    
    // Додавання користувача
    public function addUser() {
        $errors = [];
        
        // Обробка форми додавання користувача
        if (Util::isPost()) {
            $username = Util::sanitize($_POST['username']);
            $password = $_POST['password'];
            $role = Util::sanitize($_POST['role']);
            $name = Util::sanitize($_POST['name']);
            $email = Util::sanitize($_POST['email']);
            $phone = Util::sanitize($_POST['phone']);
            
            // Валідація
            if (empty($username)) {
                $errors['username'] = 'Ім\'я користувача не може бути порожнім';
            } elseif (strlen($username) < 3) {
                $errors['username'] = 'Ім\'я користувача повинно містити не менше 3 символів';
            }
            
            if (empty($password)) {
                $errors['password'] = 'Пароль не може бути порожнім';
            } elseif (strlen($password) < 6) {
                $errors['password'] = 'Пароль повинен містити не менше 6 символів';
            }
            
            if (empty($role) || !in_array($role, ['admin', 'warehouse_manager', 'supplier'])) {
                $errors['role'] = 'Виберіть роль користувача';
            }
            
            if (empty($name)) {
                $errors['name'] = 'Ім\'я не може бути порожнім';
            }
            
            if (empty($email)) {
                $errors['email'] = 'Email не може бути порожнім';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Некоректний формат email';
            }
            
            // Перевірка, чи існує користувач з таким ім'ям або email
            if ($this->userModel->isUserExist($username, $email)) {
                $errors['username'] = 'Користувач з таким ім\'ям або email вже існує';
            }
            
            // Якщо помилок немає, додаємо користувача
            if (empty($errors)) {
                if ($this->userModel->add($username, $password, $role, $name, $email, $phone)) {
                    $_SESSION['success'] = 'Користувача успішно додано';
                    Util::redirect(BASE_URL . '/admin/users');
                } else {
                    $_SESSION['error'] = 'Помилка при додаванні користувача';
                }
            }
        }
        
        $data = [
            'title' => 'Додавання користувача',
            'errors' => $errors
        ];
        
        require VIEWS_PATH . '/admin/add_user.php';
    }
    
    // Редагування користувача
    public function editUser($id) {
        $user = $this->userModel->getById($id);
        
        if (!$user) {
            $_SESSION['error'] = 'Користувача не знайдено';
            Util::redirect(BASE_URL . '/admin/users');
        }
        
        $errors = [];
        
        // Обробка форми редагування користувача
        if (Util::isPost()) {
            $name = Util::sanitize($_POST['name']);
            $email = Util::sanitize($_POST['email']);
            $phone = Util::sanitize($_POST['phone']);
            $new_password = $_POST['new_password'];
            
            // Валідація
            if (empty($name)) {
                $errors['name'] = 'Ім\'я не може бути порожнім';
            }
            
            if (empty($email)) {
                $errors['email'] = 'Email не може бути порожнім';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Некоректний формат email';
            }
            
            // Якщо вказано новий пароль, перевіряємо його
            if (!empty($new_password) && strlen($new_password) < 6) {
                $errors['new_password'] = 'Пароль повинен містити не менше 6 символів';
            }
            
            // Якщо помилок немає, оновлюємо користувача
            if (empty($errors)) {
                // Оновлюємо дані користувача
                if ($this->userModel->update($id, $name, $email, $phone)) {
                    // Якщо вказано новий пароль, змінюємо його
                    if (!empty($new_password)) {
                        $this->userModel->changePassword($id, $new_password);
                    }
                    
                    $_SESSION['success'] = 'Дані користувача успішно оновлено';
                    Util::redirect(BASE_URL . '/admin/users');
                } else {
                    $_SESSION['error'] = 'Помилка при оновленні даних користувача';
                }
            }
        }
        
        $data = [
            'title' => 'Редагування користувача',
            'user' => $user,
            'errors' => $errors
        ];
        
        require VIEWS_PATH . '/admin/edit_user.php';
    }
    
    // Видалення користувача
    public function deleteUser($id) {
        // Перевіряємо, чи не намагаємось видалити поточного користувача
        if ($id == Auth::getCurrentUserId()) {
            $_SESSION['error'] = 'Ви не можете видалити свій власний обліковий запис';
            Util::redirect(BASE_URL . '/admin/users');
        }
        
        if ($this->userModel->delete($id)) {
            $_SESSION['success'] = 'Користувача успішно видалено';
        } else {
            $_SESSION['error'] = 'Помилка при видаленні користувача';
        }
        
        Util::redirect(BASE_URL . '/admin/users');
    }
    
    // Управління рецептами
    public function recipes() {
        $data = [
            'title' => 'Управління рецептами',
            'recipes' => $this->recipeModel->getAll()
        ];
        
        require VIEWS_PATH . '/admin/recipes.php';
    }
    
    // Перегляд рецепту
    public function viewRecipe($id) {
        $recipe = $this->recipeModel->getById($id);
        
        if (!$recipe) {
            $_SESSION['error'] = 'Рецепт не знайдено';
            Util::redirect(BASE_URL . '/admin/recipes');
        }
        
        $data = [
            'title' => 'Перегляд рецепту',
            'recipe' => $recipe,
            'ingredients' => $this->recipeModel->getIngredients($id),
            'cost' => $this->recipeModel->calculateCost($id)
        ];
        
        require VIEWS_PATH . '/admin/view_recipe.php';
    }
    
    // Додавання рецепту
    public function addRecipe() {
        $errors = [];
        
        // Обробка форми додавання рецепту
        if (Util::isPost()) {
            $name = Util::sanitize($_POST['name']);
            $description = Util::sanitize($_POST['description']);
            
            // Валідація
            if (empty($name)) {
                $errors['name'] = 'Назва рецепту не може бути порожньою';
            }
            
            // Якщо помилок немає, додаємо рецепт
            if (empty($errors)) {
                $recipe_id = $this->recipeModel->add($name, $description, Auth::getCurrentUserId());
                
                if ($recipe_id) {
                    $_SESSION['success'] = 'Рецепт успішно додано';
                    Util::redirect(BASE_URL . '/admin/editRecipe/' . $recipe_id);
                } else {
                    $_SESSION['error'] = 'Помилка при додаванні рецепту';
                }
            }
        }
        
        $data = [
            'title' => 'Додавання рецепту',
            'errors' => $errors
        ];
        
        require VIEWS_PATH . '/admin/add_recipe.php';
    }
    
    // Редагування рецепту
    public function editRecipe($id) {
        $recipe = $this->recipeModel->getById($id);
        
        if (!$recipe) {
            $_SESSION['error'] = 'Рецепт не знайдено';
            Util::redirect(BASE_URL . '/admin/recipes');
        }
        
        $errors = [];
        
        // Обробка форми редагування рецепту
        if (Util::isPost()) {
            $name = Util::sanitize($_POST['name']);
            $description = Util::sanitize($_POST['description']);
            
            // Валідація
            if (empty($name)) {
                $errors['name'] = 'Назва рецепту не може бути порожньою';
            }
            
            // Якщо помилок немає, оновлюємо рецепт
            if (empty($errors)) {
                if ($this->recipeModel->update($id, $name, $description)) {
                    $_SESSION['success'] = 'Рецепт успішно оновлено';
                    Util::redirect(BASE_URL . '/admin/viewRecipe/' . $id);
                } else {
                    $_SESSION['error'] = 'Помилка при оновленні рецепту';
                }
            }
        }
        
        $data = [
            'title' => 'Редагування рецепту',
            'recipe' => $recipe,
            'ingredients' => $this->recipeModel->getIngredients($id),
            'materials' => $this->rawMaterialModel->getAll(),
            'errors' => $errors
        ];
        
        require VIEWS_PATH . '/admin/edit_recipe.php';
    }
    
    // Додавання інгредієнта до рецепту
    public function addIngredient($recipe_id) {
        $recipe = $this->recipeModel->getById($recipe_id);
        
        if (!$recipe) {
            $_SESSION['error'] = 'Рецепт не знайдено';
            Util::redirect(BASE_URL . '/admin/recipes');
        }
        
        $errors = [];
        
        // Обробка форми додавання інгредієнта
        if (Util::isPost()) {
            $raw_material_id = Util::sanitize($_POST['raw_material_id']);
            $quantity = Util::sanitize($_POST['quantity']);
            
            // Валідація
            if (empty($raw_material_id)) {
                $errors['raw_material_id'] = 'Виберіть сировину';
            }
            
            if (empty($quantity) || !is_numeric($quantity) || $quantity <= 0) {
                $errors['quantity'] = 'Кількість повинна бути більше нуля';
            }
            
            // Якщо помилок немає, додаємо інгредієнт
            if (empty($errors)) {
                if ($this->recipeModel->addIngredient($recipe_id, $raw_material_id, $quantity)) {
                    $_SESSION['success'] = 'Інгредієнт успішно додано';
                    Util::redirect(BASE_URL . '/admin/editRecipe/' . $recipe_id);
                } else {
                    $_SESSION['error'] = 'Помилка при додаванні інгредієнта';
                }
            }
        }
        
        $data = [
            'title' => 'Додавання інгредієнта',
            'recipe' => $recipe,
            'materials' => $this->rawMaterialModel->getAll(),
            'errors' => $errors
        ];
        
        require VIEWS_PATH . '/admin/add_ingredient.php';
    }
    
    // Редагування інгредієнта рецепту
    public function editIngredient($id) {
        // Отримуємо інформацію про інгредієнт
        $sql = "SELECT ri.*, r.id as recipe_id, r.name as recipe_name, rm.name as material_name, rm.unit
                FROM recipe_ingredients ri
                JOIN recipes r ON ri.recipe_id = r.id
                JOIN raw_materials rm ON ri.raw_material_id = rm.id
                WHERE ri.id = ?";
                
        $db = Database::getInstance();
        $ingredient = $db->single($sql, [$id]);
        
        if (!$ingredient) {
            $_SESSION['error'] = 'Інгредієнт не знайдено';
            Util::redirect(BASE_URL . '/admin/recipes');
        }
        
        $errors = [];
        
        // Обробка форми редагування інгредієнта
        if (Util::isPost()) {
            $quantity = Util::sanitize($_POST['quantity']);
            
            // Валідація
            if (empty($quantity) || !is_numeric($quantity) || $quantity <= 0) {
                $errors['quantity'] = 'Кількість повинна бути більше нуля';
            }
            
            // Якщо помилок немає, оновлюємо інгредієнт
            if (empty($errors)) {
                if ($this->recipeModel->updateIngredient($id, $quantity)) {
                    $_SESSION['success'] = 'Інгредієнт успішно оновлено';
                    Util::redirect(BASE_URL . '/admin/editRecipe/' . $ingredient['recipe_id']);
                } else {
                    $_SESSION['error'] = 'Помилка при оновленні інгредієнта';
                }
            }
        }
        
        $data = [
            'title' => 'Редагування інгредієнта',
            'ingredient' => $ingredient,
            'errors' => $errors
        ];
        
        require VIEWS_PATH . '/admin/edit_ingredient.php';
    }
    
    // Видалення інгредієнта рецепту
    public function deleteIngredient($id) {
        // Отримуємо ID рецепту для перенаправлення
        $sql = "SELECT recipe_id FROM recipe_ingredients WHERE id = ?";
        $db = Database::getInstance();
        $result = $db->single($sql, [$id]);
        
        if (!$result) {
            $_SESSION['error'] = 'Інгредієнт не знайдено';
            Util::redirect(BASE_URL . '/admin/recipes');
        }
        
        $recipe_id = $result['recipe_id'];
        
        if ($this->recipeModel->deleteIngredient($id)) {
            $_SESSION['success'] = 'Інгредієнт успішно видалено';
        } else {
            $_SESSION['error'] = 'Помилка при видаленні інгредієнта';
        }
        
        Util::redirect(BASE_URL . '/admin/editRecipe/' . $recipe_id);
    }
    
    // Видалення рецепту
    public function deleteRecipe($id) {
        if ($this->recipeModel->delete($id)) {
            $_SESSION['success'] = 'Рецепт успішно видалено';
        } else {
            $_SESSION['error'] = 'Помилка при видаленні рецепту';
        }
        
        Util::redirect(BASE_URL . '/admin/recipes');
    }
    
    // Управління продукцією
    public function products() {
        $data = [
            'title' => 'Управління продукцією',
            'products' => $this->productModel->getAll()
        ];
        
        require VIEWS_PATH . '/admin/products.php';
    }
    
    // Додавання продукту
    public function addProduct() {
        $errors = [];
        
        // Обробка форми додавання продукту
        if (Util::isPost()) {
            $name = Util::sanitize($_POST['name']);
            $description = Util::sanitize($_POST['description']);
            $recipe_id = Util::sanitize($_POST['recipe_id']);
            $weight = Util::sanitize($_POST['weight']);
            $price = Util::sanitize($_POST['price']);
            
            // Валідація
            if (empty($name)) {
                $errors['name'] = 'Назва продукту не може бути порожньою';
            }
            
            if (empty($recipe_id)) {
                $errors['recipe_id'] = 'Виберіть рецепт';
            }
            
            if (empty($weight) || !is_numeric($weight) || $weight <= 0) {
                $errors['weight'] = 'Вага повинна бути більше нуля';
            }
            
            if (empty($price) || !is_numeric($price) || $price <= 0) {
                $errors['price'] = 'Ціна повинна бути більше нуля';
            }
            
            // Якщо помилок немає, додаємо продукт
            if (empty($errors)) {
                if ($this->productModel->add($name, $description, $recipe_id, $weight, $price)) {
                    $_SESSION['success'] = 'Продукт успішно додано';
                    Util::redirect(BASE_URL . '/admin/products');
                } else {
                    $_SESSION['error'] = 'Помилка при додаванні продукту';
                }
            }
        }
        
        $data = [
            'title' => 'Додавання продукту',
            'recipes' => $this->recipeModel->getAll(),
            'errors' => $errors
        ];
        
        require VIEWS_PATH . '/admin/add_product.php';
    }
    
    // Редагування продукту
    public function editProduct($id) {
        $product = $this->productModel->getById($id);
        
        if (!$product) {
            $_SESSION['error'] = 'Продукт не знайдено';
            Util::redirect(BASE_URL . '/admin/products');
        }
        
        $errors = [];
        
        // Обробка форми редагування продукту
        if (Util::isPost()) {
            $name = Util::sanitize($_POST['name']);
            $description = Util::sanitize($_POST['description']);
            $recipe_id = Util::sanitize($_POST['recipe_id']);
            $weight = Util::sanitize($_POST['weight']);
            $price = Util::sanitize($_POST['price']);
            
            // Валідація
            if (empty($name)) {
                $errors['name'] = 'Назва продукту не може бути порожньою';
            }
            
            if (empty($recipe_id)) {
                $errors['recipe_id'] = 'Виберіть рецепт';
            }
            
            if (empty($weight) || !is_numeric($weight) || $weight <= 0) {
                $errors['weight'] = 'Вага повинна бути більше нуля';
            }
            
            if (empty($price) || !is_numeric($price) || $price <= 0) {
                $errors['price'] = 'Ціна повинна бути більше нуля';
            }
            
            // Якщо помилок немає, оновлюємо продукт
            if (empty($errors)) {
                if ($this->productModel->update($id, $name, $description, $recipe_id, $weight, $price)) {
                    $_SESSION['success'] = 'Продукт успішно оновлено';
                    Util::redirect(BASE_URL . '/admin/products');
                } else {
                    $_SESSION['error'] = 'Помилка при оновленні продукту';
                }
            }
        }
        
        $data = [
            'title' => 'Редагування продукту',
            'product' => $product,
            'recipes' => $this->recipeModel->getAll(),
            'errors' => $errors
        ];
        
        require VIEWS_PATH . '/admin/edit_product.php';
    }
    
    // Видалення продукту
    public function deleteProduct($id) {
        if ($this->productModel->delete($id)) {
            $_SESSION['success'] = 'Продукт успішно видалено';
        } else {
            $_SESSION['error'] = 'Помилка при видаленні продукту';
        }
        
        Util::redirect(BASE_URL . '/admin/products');
    }

   


    
public function videoSurveillance() {
    // Отримуємо всі активні камери 
    $cameras = $this->videoSurveillanceModel->getActive();
    
    // Передаємо дані в шаблон
    $data = [
        'title' => 'Відеоспостереження',
        'cameras' => $cameras
    ];
    
    require VIEWS_PATH . '/admin/video_surveillance.php';
}

// Метод для встановлення статусу камери
public function setCameraStatus($id, $status) {
    if ($status !== 'active' && $status !== 'inactive') {
        $_SESSION['error'] = 'Невірний статус камери';
        Util::redirect(BASE_URL . '/admin/cameras');
    }
    
    if ($this->videoSurveillanceModel->setStatus($id, $status)) {
        $_SESSION['success'] = 'Статус камери успішно змінено';
    } else {
        $_SESSION['error'] = 'Помилка при зміні статусу камери';
    }
    
    Util::redirect(BASE_URL . '/admin/cameras');
}
    
    // Управління камерами
    public function cameras() {
        try {
            $cameras = $this->videoSurveillanceModel->getAll();
            
            $data = [
                'title' => 'Управління камерами',
                'cameras' => $cameras ?: []
            ];
            
        } catch (Exception $e) {
            Util::log("Cameras loading error: " . $e->getMessage(), 'error');
            $data = [
                'title' => 'Управління камерами',
                'cameras' => []
            ];
        }
        
        require VIEWS_PATH . '/admin/cameras.php';
    }
    
    // Додавання камери
    public function addCamera() {
        $errors = [];
        
        // Обробка форми додавання камери
        if (Util::isPost()) {
            $name = Util::sanitize($_POST['name']);
            $url = Util::sanitize($_POST['url']);
            $location = Util::sanitize($_POST['location']);
            
            // Валідація
            if (empty($name)) {
                $errors['name'] = 'Назва камери не може бути порожньою';
            }
            
            if (empty($url)) {
                $errors['url'] = 'URL камери не може бути порожнім';
            }
            
            if (empty($location)) {
                $errors['location'] = 'Розташування камери не може бути порожнім';
            }
            
            // Якщо помилок немає, додаємо камеру
            if (empty($errors)) {
                if ($this->videoSurveillanceModel->add($name, $url, $location)) {
                    $_SESSION['success'] = 'Камеру успішно додано';
                    Util::redirect(BASE_URL . '/admin/cameras');
                } else {
                    $_SESSION['error'] = 'Помилка при додаванні камери';
                }
            }
        }
        
        $data = [
            'title' => 'Додавання камери',
            'errors' => $errors,
            'locations' => $this->videoSurveillanceModel->getLocations()
        ];
        
        require VIEWS_PATH . '/admin/add_camera.php';
    }
    
    // Редагування камери
    public function editCamera($id) {
        $camera = $this->videoSurveillanceModel->getById($id);
        
        if (!$camera) {
            $_SESSION['error'] = 'Камеру не знайдено';
            Util::redirect(BASE_URL . '/admin/cameras');
        }
        
        $errors = [];
        
        // Обробка форми редагування камери
        if (Util::isPost()) {
            $name = Util::sanitize($_POST['name']);
            $url = Util::sanitize($_POST['url']);
            $location = Util::sanitize($_POST['location']);
            $status = Util::sanitize($_POST['status']);
            
            // Валідація
            if (empty($name)) {
                $errors['name'] = 'Назва камери не може бути порожньою';
            }
            
            if (empty($url)) {
                $errors['url'] = 'URL камери не може бути порожнім';
            }
            
            if (empty($location)) {
                $errors['location'] = 'Розташування камери не може бути порожнім';
            }
            
            if (empty($status) || !in_array($status, ['active', 'inactive'])) {
                $errors['status'] = 'Виберіть статус камери';
            }
            
            // Якщо помилок немає, оновлюємо камеру
            if (empty($errors)) {
                if ($this->videoSurveillanceModel->update($id, $name, $url, $location, $status)) {
                    $_SESSION['success'] = 'Камеру успішно оновлено';
                    Util::redirect(BASE_URL . '/admin/cameras');
                } else {
                    $_SESSION['error'] = 'Помилка при оновленні камери';
                }
            }
        }
        
        $data = [
            'title' => 'Редагування камери',
            'camera' => $camera,
            'errors' => $errors,
            'locations' => $this->videoSurveillanceModel->getLocations()
        ];
        
        require VIEWS_PATH . '/admin/edit_camera.php';
    }
    
    // Видалення камери
    public function deleteCamera($id) {
        if ($this->videoSurveillanceModel->delete($id)) {
            $_SESSION['success'] = 'Камеру успішно видалено';
        } else {
            $_SESSION['error'] = 'Помилка при видаленні камери';
        }
        
        Util::redirect(BASE_URL . '/admin/cameras');
    }
    
    // Управління замовленнями сировини
    public function orders() {
        $data = [
            'title' => 'Замовлення сировини',
            'orders' => $this->orderModel->getAll()
        ];
        
        require VIEWS_PATH . '/admin/orders.php';
    }
    
    // Створення замовлення
    public function createOrder() {
        $errors = [];
        
        // Обробка форми створення замовлення
        if (Util::isPost()) {
            $supplier_id = Util::sanitize($_POST['supplier_id']);
            $delivery_date = Util::sanitize($_POST['delivery_date']);
            $notes = Util::sanitize($_POST['notes']);
            
            // Валідація
            if (empty($supplier_id)) {
                $errors['supplier_id'] = 'Виберіть постачальника';
            }
            
            if (empty($delivery_date)) {
                $errors['delivery_date'] = 'Виберіть дату доставки';
            } elseif (strtotime($delivery_date) < strtotime(date('Y-m-d'))) {
                $errors['delivery_date'] = 'Дата доставки не може бути в минулому';
            }
            
            // Якщо помилок немає, створюємо замовлення
            if (empty($errors)) {
                $order_id = $this->orderModel->create($supplier_id, Auth::getCurrentUserId(), $delivery_date, $notes);
                
                if ($order_id) {
                    $_SESSION['success'] = 'Замовлення успішно створено';
                    Util::redirect(BASE_URL . '/admin/editOrder/' . $order_id);
                } else {
                    $_SESSION['error'] = 'Помилка при створенні замовлення';
                }
            }
        }
        
        $data = [
            'title' => 'Створення замовлення',
            'suppliers' => $this->userModel->getSuppliers(),
            'errors' => $errors
        ];
        
        require VIEWS_PATH . '/admin/create_order.php';
    }
    
    // Редагування замовлення
    public function editOrder($id) {
        $order = $this->orderModel->getById($id);
        
        if (!$order) {
            $_SESSION['error'] = 'Замовлення не знайдено';
            Util::redirect(BASE_URL . '/admin/orders');
        }
        
        // Перевіряємо, чи можна редагувати замовлення
        if ($order['status'] !== 'pending') {
            $_SESSION['error'] = 'Можна редагувати тільки замовлення в статусі "Очікує підтвердження"';
            Util::redirect(BASE_URL . '/admin/viewOrder/' . $id);
        }
        
        $data = [
            'title' => 'Редагування замовлення',
            'order' => $order,
            'items' => $this->orderModel->getItems($id),
            'materials' => $this->rawMaterialModel->getBySupplier($order['supplier_id'])
        ];
        
        require VIEWS_PATH . '/admin/edit_order.php';
    }
    
    // Додавання елемента до замовлення
public function addOrderItem($order_id) {
    $order = $this->orderModel->getById($order_id);
    
    if (!$order) {
        $_SESSION['error'] = 'Замовлення не знайдено';
        Util::redirect(BASE_URL . '/admin/orders');
        return;
    }
    
    // Перевіряємо, чи можна редагувати замовлення
    if ($order['status'] !== 'pending') {
        $_SESSION['error'] = 'Можна редагувати тільки замовлення в статусі "Очікує підтвердження"';
        Util::redirect(BASE_URL . '/admin/viewOrder/' . $order_id);
        return;
    }
    
    $errors = [];
    
    // Обробка форми додавання елемента
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $raw_material_id = isset($_POST['raw_material_id']) ? trim($_POST['raw_material_id']) : '';
        $quantity = isset($_POST['quantity']) ? trim($_POST['quantity']) : '';
        $price_per_unit = isset($_POST['price_per_unit']) ? trim($_POST['price_per_unit']) : '';
        
        // Валідація
        if (empty($raw_material_id)) {
            $errors['raw_material_id'] = 'Виберіть сировину';
        }
        
        if (empty($quantity) || !is_numeric($quantity) || $quantity <= 0) {
            $errors['quantity'] = 'Кількість повинна бути більше нуля';
        }
        
        if (empty($price_per_unit) || !is_numeric($price_per_unit) || $price_per_unit <= 0) {
            $errors['price_per_unit'] = 'Ціна повинна бути більше нуля';
        }
        
        // Якщо помилок немає, додаємо елемент
        if (empty($errors)) {
            if ($this->orderModel->addItem($order_id, $raw_material_id, $quantity, $price_per_unit)) {
                $_SESSION['success'] = 'Елемент замовлення успішно додано';
                Util::redirect(BASE_URL . '/admin/editOrder/' . $order_id);
                return;
            } else {
                $_SESSION['error'] = 'Помилка при додаванні елемента замовлення';
            }
        }
    }
    
    // Отримуємо матеріали постачальника
    $materials = $this->rawMaterialModel->getBySupplier($order['supplier_id']);
    
    // Якщо є material_id в GET параметрах, автоматично вибираємо матеріал
    if (isset($_GET['material_id']) && !isset($_POST['raw_material_id'])) {
        $_POST['raw_material_id'] = $_GET['material_id'];
        
        // Автоматично заповнюємо ціну
        $material = $this->rawMaterialModel->getById($_GET['material_id']);
        if ($material) {
            $_POST['price_per_unit'] = $material['price_per_unit'];
        }
    }
    
    $data = [
        'title' => 'Додавання елемента замовлення',
        'order' => $order,
        'materials' => $materials,
        'errors' => $errors
    ];
    
    require VIEWS_PATH . '/admin/add_order_item.php';
}
    
    // Перегляд замовлення
    public function viewOrder($id) {
        $order = $this->orderModel->getById($id);
        
        if (!$order) {
            $_SESSION['error'] = 'Замовлення не знайдено';
            Util::redirect(BASE_URL . '/admin/orders');
        }
        
        $data = [
            'title' => 'Перегляд замовлення',
            'order' => $order,
            'items' => $this->orderModel->getItems($id)
        ];
        
        require VIEWS_PATH . '/admin/view_order.php';
    }
    
    // Підтвердження отримання замовлення
    public function deliverOrder($id) {
        $order = $this->orderModel->getById($id);
        
        if (!$order) {
            $_SESSION['error'] = 'Замовлення не знайдено';
            Util::redirect(BASE_URL . '/admin/orders');
        }
        
        // Перевіряємо, чи можна підтвердити отримання
        if ($order['status'] !== 'shipped') {
            $_SESSION['error'] = 'Можна підтвердити отримання тільки для замовлень в статусі "Відправлено"';
            Util::redirect(BASE_URL . '/admin/viewOrder/' . $id);
        }
        
        if ($this->orderModel->deliver($id, $this->inventoryModel)) {
            $_SESSION['success'] = 'Отримання замовлення успішно підтверджено';
        } else {
            $_SESSION['error'] = 'Помилка при підтвердженні отримання замовлення';
        }
        
        Util::redirect(BASE_URL . '/admin/viewOrder/' . $id);
    }
    
    // Скасування замовлення
    public function cancelOrder($id) {
        $order = $this->orderModel->getById($id);
        
        if (!$order) {
            $_SESSION['error'] = 'Замовлення не знайдено';
            Util::redirect(BASE_URL . '/admin/orders');
        }
        
        // Перевіряємо, чи можна скасувати замовлення
        if ($order['status'] === 'delivered' || $order['status'] === 'canceled') {
            $_SESSION['error'] = 'Неможливо скасувати замовлення в статусі "Доставлено" або "Скасовано"';
            Util::redirect(BASE_URL . '/admin/viewOrder/' . $id);
        }
        
        if ($this->orderModel->cancel($id)) {
            $_SESSION['success'] = 'Замовлення успішно скасовано';
        } else {
            $_SESSION['error'] = 'Помилка при скасуванні замовлення';
        }
        
        Util::redirect(BASE_URL . '/admin/viewOrder/' . $id);
    }
    
// Редагування елемента замовлення
public function editOrderItem($id) {
    // Отримуємо інформацію про елемент замовлення
    $sql = "SELECT oi.*, o.id as order_id, o.supplier_id, rm.name as material_name, rm.unit
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN raw_materials rm ON oi.raw_material_id = rm.id
            WHERE oi.id = ?";
            
    $db = Database::getInstance();
    $item = $db->single($sql, [$id]);
    
    if (!$item) {
        $_SESSION['error'] = 'Елемент замовлення не знайдено';
        Util::redirect(BASE_URL . '/admin/orders');
    }
    
    // Перевіряємо статус замовлення
    $order = $this->orderModel->getById($item['order_id']);
    if ($order['status'] !== 'pending') {
        $_SESSION['error'] = 'Можна редагувати тільки замовлення в статусі "Очікує підтвердження"';
        Util::redirect(BASE_URL . '/admin/viewOrder/' . $item['order_id']);
    }
    
    $errors = [];
    
    // Обробка форми редагування елемента
    if (Util::isPost()) {
        $quantity = Util::sanitize($_POST['quantity']);
        $price_per_unit = Util::sanitize($_POST['price_per_unit']);
        
        // Валідація
        if (empty($quantity) || !is_numeric($quantity) || $quantity <= 0) {
            $errors['quantity'] = 'Кількість повинна бути більше нуля';
        }
        
        if (empty($price_per_unit) || !is_numeric($price_per_unit) || $price_per_unit <= 0) {
            $errors['price_per_unit'] = 'Ціна повинна бути більше нуля';
        }
        
        // Якщо помилок немає, оновлюємо елемент
        if (empty($errors)) {
            $sql = "UPDATE order_items SET quantity = ?, price_per_unit = ? WHERE id = ?";
            
            if ($db->query($sql, [$quantity, $price_per_unit, $id])) {
                // Оновлюємо загальну суму замовлення
                $this->orderModel->updateTotalAmount($item['order_id']);
                
                $_SESSION['success'] = 'Позицію замовлення успішно оновлено';
                Util::redirect(BASE_URL . '/admin/editOrder/' . $item['order_id']);
            } else {
                $_SESSION['error'] = 'Помилка при оновленні позиції замовлення';
            }
        }
    }
    
    $data = [
        'title' => 'Редагування позиції замовлення',
        'item' => $item,
        'order' => $order,
        'errors' => $errors
    ];
    
    require VIEWS_PATH . '/admin/edit_order_item.php';
}

// Видалення елемента замовлення
public function deleteOrderItem($id) {
    // Отримуємо інформацію про елемент замовлення
    $sql = "SELECT order_id FROM order_items WHERE id = ?";
    $db = Database::getInstance();
    $result = $db->single($sql, [$id]);
    
    if (!$result) {
        $_SESSION['error'] = 'Елемент замовлення не знайдено';
        Util::redirect(BASE_URL . '/admin/orders');
    }
    
    $order_id = $result['order_id'];
    
    // Перевіряємо статус замовлення
    $order = $this->orderModel->getById($order_id);
    if ($order['status'] !== 'pending') {
        $_SESSION['error'] = 'Можна видаляти позиції тільки з замовлень в статусі "Очікує підтвердження"';
        Util::redirect(BASE_URL . '/admin/viewOrder/' . $order_id);
    }
    
    // Видаляємо елемент
    $sql = "DELETE FROM order_items WHERE id = ?";
    
    if ($db->query($sql, [$id])) {
        // Оновлюємо загальну суму замовлення
        $this->orderModel->updateTotalAmount($order_id);
        
        $_SESSION['success'] = 'Позицію замовлення успішно видалено';
    } else {
        $_SESSION['error'] = 'Помилка при видаленні позиції замовлення';
    }
    
    Util::redirect(BASE_URL . '/admin/editOrder/' . $order_id);
}

    // Звіти
    public function reports() {
        $data = [
            'title' => 'Звіти'
        ];
        
        require VIEWS_PATH . '/admin/reports.php';
    }
    
    // Звіт по запасам
    public function inventoryReport() {
        try {
            $inventory = $this->inventoryModel->getStockReport();
            
            $data = [
                'title' => 'Звіт по запасам',
                'inventory' => $inventory ?: []
            ];
            
        } catch (Exception $e) {
            Util::log("Inventory report error: " . $e->getMessage(), 'error');
            $data = [
                'title' => 'Звіт по запасам',
                'inventory' => []
            ];
        }
        
        require VIEWS_PATH . '/admin/inventory_report.php';
    }
    
    // Звіт по виробництву
    public function productionReport() {
        try {
            // Параметры периода (по умолчанию - текущий месяц)
            $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
            $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
            
            $stats = $this->productionModel->getDetailedStatsByPeriod($start_date, $end_date);
            $daily_stats = $this->productionModel->getStatsByPeriod($start_date, $end_date);
            $products_stats = $this->productModel->getProductionStats($start_date, $end_date);
            
            $data = [
                'title' => 'Звіт по виробництву',
                'start_date' => $start_date,
                'end_date' => $end_date,
                'stats' => $stats ?: [],
                'daily_stats' => $daily_stats ?: [],
                'products_stats' => $products_stats ?: []
            ];
            
        } catch (Exception $e) {
            Util::log("Production report error: " . $e->getMessage(), 'error');
            $data = [
                'title' => 'Звіт по виробництву',
                'start_date' => date('Y-m-01'),
                'end_date' => date('Y-m-t'),
                'stats' => [],
                'daily_stats' => [],
                'products_stats' => []
            ];
        }
        
        require VIEWS_PATH . '/admin/production_report.php';
    }
    
    // Звіт по замовленнях
    public function ordersReport() {
        try {
            // Параметры периода (по умолчанию - текущий месяц)
            $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
            $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
            
            $daily_stats = $this->orderModel->getStatsByPeriod($start_date, $end_date);
            $supplier_stats = $this->orderModel->getStatsBySupplier($start_date, $end_date);
            $material_stats = $this->orderModel->getStatsByMaterial($start_date, $end_date);
            
            // Вычисляем дополнительные метрики
            $ordersCount = array_sum(array_column($supplier_stats, 'orders_count'));
            $totalAmount = array_sum(array_column($supplier_stats, 'total_amount'));
            
            $data = [
                'title' => 'Звіт по замовленнях',
                'start_date' => $start_date,
                'end_date' => $end_date,
                'daily_stats' => $daily_stats ?: [],
                'supplier_stats' => $supplier_stats ?: [],
                'material_stats' => $material_stats ?: [],
                'ordersCount' => $ordersCount,
                'totalAmount' => $totalAmount
            ];
            
            // Передаем переменные в область видимости представления
            extract($data);
            
        } catch (Exception $e) {
            Util::log("Orders report error: " . $e->getMessage(), 'error');
            
            // Устанавливаем значения по умолчанию при ошибке
            $start_date = date('Y-m-01');
            $end_date = date('Y-m-t');
            $daily_stats = [];
            $supplier_stats = [];
            $material_stats = [];
            $ordersCount = 0;
            $totalAmount = 0;
        }
        
        require VIEWS_PATH . '/admin/orders_report.php';
    }
    
    // Генерація PDF звіту по запасам
    public function generateInventoryPdf() {
        $inventory = $this->inventoryModel->getStockReport();
        
        $pdf = new PDF('Звіт по запасам');
        $pdf->addTitle('Звіт по запасам на ' . date('d.m.Y'));
        
        // Підготовка даних для таблиці
        $header = ['Назва', 'Кількість', 'Одиниці', 'Мін. запас', 'Ціна за од.', 'Загальна вартість', 'Статус'];
        $data = [];
        
        foreach ($inventory as $item) {
            $status = '';
            switch ($item['status']) {
                case 'low':
                    $status = 'Критично';
                    break;
                case 'medium':
                    $status = 'Середньо';
                    break;
                case 'good':
                    $status = 'Достатньо';
                    break;
            }
            
            $data[] = [
                $item['name'],
                number_format($item['quantity'], 2),
                $item['unit'],
                number_format($item['min_stock'], 2),
                number_format($item['price_per_unit'], 2) . ' грн',
                number_format($item['total_value'], 2) . ' грн',
                $status
            ];
        }
        
        $pdf->addTable($header, $data);
        
        // Загальна вартість
        $total_value = array_sum(array_column($inventory, 'total_value'));
        $pdf->addText('Загальна вартість запасів: ' . number_format($total_value, 2) . ' грн');
        
        $pdf->addDateAndSignature();
        $pdf->output('inventory_report_' . date('Y-m-d') . '.pdf');
    }
    
    // Генерація PDF звіту по виробництву
    public function generateProductionPdf() {
        // Параметри періоду
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
        
        $stats = $this->productionModel->getDetailedStatsByPeriod($start_date, $end_date);
        $products_stats = $this->productModel->getProductionStats($start_date, $end_date);
        
        $pdf = new PDF('Звіт по виробництву');
        $pdf->addTitle('Звіт по виробництву за період', 'з ' . date('d.m.Y', strtotime($start_date)) . ' по ' . date('d.m.Y', strtotime($end_date)));
        
        // Підготовка даних для таблиці статистики по продуктах
        $header = ['Продукт', 'Кількість', 'Кількість циклів', 'Сер. час виробництва'];
        $data = [];
        
        foreach ($stats as $item) {
            $data[] = [
                $item['product_name'],
                number_format($item['total_quantity'], 2),
                $item['processes_count'],
                round($item['avg_production_time'], 1) . ' год'
            ];
        }
        
        $pdf->addText('Статистика виробництва по продуктах:');
        $pdf->addTable($header, $data);
        
        // Підготовка даних для таблиці вартості продукції
        $header = ['Продукт', 'Кількість', 'Ціна', 'Загальна вартість'];
        $data = [];
        
        foreach ($products_stats as $item) {
            $data[] = [
                $item['name'],
                number_format($item['total_produced'], 2),
                number_format($item['price'], 2) . ' грн',
                number_format($item['total_value'], 2) . ' грн'
            ];
        }
        
        $pdf->addText('Вартість виробленої продукції:');
        $pdf->addTable($header, $data);
        
        // Загальна вартість
        $total_value = array_sum(array_column($products_stats, 'total_value'));
        $pdf->addText('Загальна вартість виробленої продукції: ' . number_format($total_value, 2) . ' грн');
        
        $pdf->addDateAndSignature();
        $pdf->output('production_report_' . date('Y-m-d') . '.pdf');
    }
    
    // Генерація PDF звіту по замовленнях
    public function generateOrdersPdf() {
        // Параметри періоду
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
        
        $supplier_stats = $this->orderModel->getStatsBySupplier($start_date, $end_date);
        $material_stats = $this->orderModel->getStatsByMaterial($start_date, $end_date);
        
        $pdf = new PDF('Звіт по замовленнях');
        $pdf->addTitle('Звіт по замовленнях за період', 'з ' . date('d.m.Y', strtotime($start_date)) . ' по ' . date('d.m.Y', strtotime($end_date)));
        
        // Підготовка даних для таблиці статистики по постачальниках
        $header = ['Постачальник', 'Кількість замовлень', 'Загальна сума'];
        $data = [];
        
        foreach ($supplier_stats as $item) {
            $data[] = [
                $item['supplier_name'],
                $item['orders_count'],
                number_format($item['total_amount'], 2) . ' грн'
            ];
        }
        
        $pdf->addText('Статистика замовлень по постачальниках:');
        $pdf->addTable($header, $data);
        
        // Підготовка даних для таблиці статистики по матеріалах
        $header = ['Матеріал', 'Одиниці', 'Кількість', 'Загальна сума'];
        $data = [];
        
        foreach ($material_stats as $item) {
            $data[] = [
                $item['material_name'],
                $item['unit'],
                number_format($item['total_quantity'], 2),
                number_format($item['total_amount'], 2) . ' грн'
            ];
        }
        
        $pdf->addText('Статистика замовлень по матеріалах:');
        $pdf->addTable($header, $data);
        
        // Загальна сума
        $total_amount = array_sum(array_column($supplier_stats, 'total_amount'));
        $pdf->addText('Загальна сума замовлень: ' . number_format($total_amount, 2) . ' грн');
        
        $pdf->addDateAndSignature();
        $pdf->output('orders_report_' . date('Y-m-d') . '.pdf');
    }

    // Метод для генерації PDF замовлення
public function printOrder($id) {
    $order = $this->orderModel->getById($id);
    
    if (!$order) {
        $_SESSION['error'] = 'Замовлення не знайдено';
        Util::redirect(BASE_URL . '/admin/orders');
    }
    
    $items = $this->orderModel->getItems($id);
    
    $pdf = new PDF('Замовлення #' . $id);
    
    // Додавання заголовка
    $pdf->addTitle('Замовлення #' . $id, 'від ' . Util::formatDate($order['created_at'], 'd.m.Y'));
    
    // Інформація про замовлення
    $pdf->addText('Постачальник: ' . $order['supplier_name']);
    $pdf->addText('Email: ' . $order['supplier_email']);
    if (!empty($order['supplier_phone'])) {
        $pdf->addText('Телефон: ' . $order['supplier_phone']);
    }
    $pdf->addText('Очікувана доставка: ' . date('d.m.Y', strtotime($order['delivery_date'])));
    $pdf->addText('Статус: ' . Util::getOrderStatusName($order['status']));
    
    if (!empty($order['notes'])) {
        $pdf->addText('Примітки: ' . $order['notes']);
    }
    
    $pdf->Ln(5);
    
    // Позиції замовлення
    $header = ['Сировина', 'Кількість', 'Ціна за од.', 'Загальна сума'];
    $data = [];
    
    foreach ($items as $item) {
        $data[] = [
            $item['material_name'],
            $item['quantity'] . ' ' . $item['unit'],
            Util::formatMoney($item['price_per_unit']),
            Util::formatMoney($item['quantity'] * $item['price_per_unit'])
        ];
    }
    
    $pdf->addText('Позиції замовлення:');
    $pdf->addTable($header, $data);
    
    // Загальна сума
    $pdf->addText('Загальна сума замовлення: ' . Util::formatMoney($order['total_amount']));
    
    $pdf->addDateAndSignature();
    $pdf->output('order_' . $id . '_' . date('Y-m-d') . '.pdf');
}

// Метод для відображення сторінки звітів за обраний період
public function customReport() {
    $report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'production';
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
    
    switch ($report_type) {
        case 'production':
            Util::redirect(BASE_URL . '/admin/productionReport?start_date=' . urlencode($start_date) . '&end_date=' . urlencode($end_date));
            break;
        case 'orders':
            Util::redirect(BASE_URL . '/admin/ordersReport?start_date=' . urlencode($start_date) . '&end_date=' . urlencode($end_date));
            break;
        case 'materials':
            // Звіт по використанню сировини
            $this->materialsUsageReport($start_date, $end_date);
            break;
        default:
            $_SESSION['error'] = 'Невідомий тип звіту';
            Util::redirect(BASE_URL . '/admin/reports');
    }
}

// Звіт по використанню сировини
public function materialsUsageReport($start_date = null, $end_date = null) {
    // Якщо дати не вказані, встановлюємо значення за замовчуванням
    $start_date = $start_date ?? (isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'));
    $end_date = $end_date ?? (isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'));
    
    // Отримання статистики використання сировини
    $rawMaterialModel = new RawMaterial();
    $materials_stats = $rawMaterialModel->getUsageStats($start_date, $end_date);
    
    // Отримуємо поточні запаси
    $inventoryModel = new Inventory();
    $current_stock = $inventoryModel->getStockReport();
    
    // Готуємо дані для представлення
    $data = [
        'title' => 'Звіт по використанню сировини',
        'start_date' => $start_date,
        'end_date' => $end_date,
        'materials_stats' => $materials_stats,
        'current_stock' => $current_stock
    ];
    
    require VIEWS_PATH . '/admin/materials_usage_report.php';
}

// Метод для генерації PDF звіту по використанню сировини
public function generateMaterialsUsagePdf() {
    // Параметри періоду
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
    
    // Отримання даних
    $rawMaterialModel = new RawMaterial();
    $materials_stats = $rawMaterialModel->getUsageStats($start_date, $end_date);
    
    $pdf = new PDF('Звіт по використанню сировини');
    $pdf->addTitle('Звіт по використанню сировини за період', 'з ' . date('d.m.Y', strtotime($start_date)) . ' по ' . date('d.m.Y', strtotime($end_date)));
    
    // Підготовка даних для таблиці
    $header = ['Сировина', 'Одиниця', 'Використано', 'Поточний запас', 'Потреба на місяць'];
    $data = [];
    
    foreach ($materials_stats as $item) {
        // Розрахунок потреби на місяць (на основі використання за звітний період)
        $days_in_period = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24) + 1;
        $monthly_need = $item['total_used'] / $days_in_period * 30;
        
        $data[] = [
            $item['name'],
            $item['unit'],
            number_format($item['total_used'], 2),
            number_format($item['current_stock'], 2),
            number_format($monthly_need, 2)
        ];
    }
    
    $pdf->addTable($header, $data);
    
    // Рекомендації
    $pdf->addText('Рекомендації:');
    foreach ($materials_stats as $item) {
        if ($item['current_stock'] < $item['min_stock']) {
            $shortage = $item['min_stock'] - $item['current_stock'];
            $pdf->addText('• ' . $item['name'] . ': необхідно замовити ' . number_format($shortage, 2) . ' ' . $item['unit'] . ' для досягнення мінімального запасу.');
        }
    }
    
    $pdf->addDateAndSignature();
    $pdf->output('materials_usage_report_' . date('Y-m-d') . '.pdf');
}
 // AJAX метод для добавления позиции заказа
public function ajaxAddOrderItem() {
    header('Content-Type: application/json');
    
    if (!Util::isPost()) {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }
    
    $order_id = Util::sanitize($_POST['order_id']);
    $raw_material_id = Util::sanitize($_POST['raw_material_id']);
    $quantity = Util::sanitize($_POST['quantity']);
    $price_per_unit = Util::sanitize($_POST['price_per_unit']);
    
    // Проверка заказа
    $order = $this->orderModel->getById($order_id);
    if (!$order || $order['status'] !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'Замовлення не знайдено або не може бути змінено']);
        exit;
    }
    
    // Валидация
    if (empty($raw_material_id) || empty($quantity) || empty($price_per_unit)) {
        echo json_encode(['success' => false, 'message' => 'Заповніть всі поля']);
        exit;
    }
    
    if (!is_numeric($quantity) || $quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Кількість повинна бути більше нуля']);
        exit;
    }
    
    if (!is_numeric($price_per_unit) || $price_per_unit <= 0) {
        echo json_encode(['success' => false, 'message' => 'Ціна повинна бути більше нуля']);
        exit;
    }
    
    // Добавление позиции
    if ($this->orderModel->addItem($order_id, $raw_material_id, $quantity, $price_per_unit)) {
        echo json_encode(['success' => true, 'message' => 'Позицію успішно додано']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Помилка при додаванні позиції']);
    }
    exit;
}

// AJAX метод для удаления позиции заказа
public function ajaxDeleteOrderItem() {
    header('Content-Type: application/json');
    
    if (!Util::isPost()) {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }
    
    $item_id = Util::sanitize($_POST['item_id']);
    
    // Получаем информацию о позиции
    $sql = "SELECT oi.order_id, o.status 
            FROM order_items oi 
            JOIN orders o ON oi.order_id = o.id 
            WHERE oi.id = ?";
    $db = Database::getInstance();
    $result = $db->single($sql, [$item_id]);
    
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Позицію не знайдено']);
        exit;
    }
    
    if ($result['status'] !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'Можна видаляти позиції тільки з замовлень в статусі "Очікує підтвердження"']);
        exit;
    }
    
    // Удаление позиции
    $sql = "DELETE FROM order_items WHERE id = ?";
    if ($db->query($sql, [$item_id])) {
        // Обновляем общую сумму заказа
        $this->orderModel->updateTotalAmount($result['order_id']);
        echo json_encode(['success' => true, 'message' => 'Позицію успішно видалено']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Помилка при видаленні позиції']);
    }
    exit;
}

// AJAX метод для обновления позиции заказа
public function ajaxUpdateOrderItem() {
    header('Content-Type: application/json');
    
    if (!Util::isPost()) {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }
    
    $item_id = Util::sanitize($_POST['item_id']);
    $quantity = Util::sanitize($_POST['quantity']);
    $price_per_unit = Util::sanitize($_POST['price_per_unit']);
    
    // Проверка позиции
    $sql = "SELECT oi.*, o.status 
            FROM order_items oi 
            JOIN orders o ON oi.order_id = o.id 
            WHERE oi.id = ?";
    $db = Database::getInstance();
    $item = $db->single($sql, [$item_id]);
    
    if (!$item) {
        echo json_encode(['success' => false, 'message' => 'Позицію не знайдено']);
        exit;
    }
    
    if ($item['status'] !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'Можна редагувати тільки замовлення в статусі "Очікує підтвердження"']);
        exit;
    }
    
    // Валидация
    if (empty($quantity) || !is_numeric($quantity) || $quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Кількість повинна бути більше нуля']);
        exit;
    }
    
    if (empty($price_per_unit) || !is_numeric($price_per_unit) || $price_per_unit <= 0) {
        echo json_encode(['success' => false, 'message' => 'Ціна повинна бути більше нуля']);
        exit;
    }
    
    // Обновление позиции
    if ($this->orderModel->updateItem($item_id, $quantity, $price_per_unit)) {
        // Получаем обновленную информацию
        $updatedItem = $db->single("SELECT oi.*, rm.name as material_name, rm.unit 
                                    FROM order_items oi 
                                    JOIN raw_materials rm ON oi.raw_material_id = rm.id 
                                    WHERE oi.id = ?", [$item_id]);
        
        // Получаем обновленную общую сумму заказа
        $order = $this->orderModel->getById($item['order_id']);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Позицію успішно оновлено',
            'item' => $updatedItem,
            'totalAmount' => $order['total_amount']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Помилка при оновленні позиції']);
    }
    exit;
}

// AJAX метод для получения данных позиции
public function ajaxGetOrderItem() {
    header('Content-Type: application/json');
    
    $item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    $sql = "SELECT oi.*, rm.name as material_name, rm.unit 
            FROM order_items oi 
            JOIN raw_materials rm ON oi.raw_material_id = rm.id 
            WHERE oi.id = ?";
    
    $db = Database::getInstance();
    $item = $db->single($sql, [$item_id]);
    
    if ($item) {
        echo json_encode(['success' => true, 'item' => $item]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Позицію не знайдено']);
    }
    exit;
}
}