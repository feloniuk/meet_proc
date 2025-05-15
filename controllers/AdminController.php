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
    
    // Управління відеоспостереженням
    public function videoSurveillance() {
        $data = [
            'title' => 'Відеоспостереження',
            'cameras' => $this->videoSurveillanceModel->getActive()
        ];
        
        require VIEWS_PATH . '/admin/video_surveillance.php';
    }
    
    // Управління камерами
    public function cameras() {
        $data = [
            'title' => 'Управління камерами',
            'cameras' => $this->videoSurveillanceModel->getAll()
        ];
        
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
        }
        
        // Перевіряємо, чи можна редагувати замовлення
        if ($order['status'] !== 'pending') {
            $_SESSION['error'] = 'Можна редагувати тільки замовлення в статусі "Очікує підтвердження"';
            Util::redirect(BASE_URL . '/admin/viewOrder/' . $order_id);
        }
        
        $errors = [];
        
        // Обробка форми додавання елемента
        if (Util::isPost()) {
            $raw_material_id = Util::sanitize($_POST['raw_material_id']);
            $quantity = Util::sanitize($_POST['quantity']);
            $price_per_unit = Util::sanitize($_POST['price_per_unit']);
            
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
                } else {
                    $_SESSION['error'] = 'Помилка при додаванні елемента замовлення';
                }
            }
        }
        
        $data = [
            'title' => 'Додавання елемента замовлення',
            'order' => $order,
            'materials' => $this->rawMaterialModel->getBySupplier($order['supplier_id']),
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
    
    // Звіти
    public function reports() {
        $data = [
            'title' => 'Звіти'
        ];
        
        require VIEWS_PATH . '/admin/reports.php';
    }
    
    // Звіт по запасам
    public function inventoryReport() {
        $data = [
            'title' => 'Звіт по запасам',
            'inventory' => $this->inventoryModel->getStockReport()
        ];
        
        require VIEWS_PATH . '/admin/inventory_report.php';
    }
    
    // Звіт по виробництву
    public function productionReport() {
        // Параметри періоду (за замовчуванням - поточний місяць)
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
        
        $data = [
            'title' => 'Звіт по виробництву',
            'start_date' => $start_date,
            'end_date' => $end_date,
            'stats' => $this->productionModel->getDetailedStatsByPeriod($start_date, $end_date),
            'daily_stats' => $this->productionModel->getStatsByPeriod($start_date, $end_date),
            'products_stats' => $this->productModel->getProductionStats($start_date, $end_date)
        ];
        
        require VIEWS_PATH . '/admin/production_report.php';
    }
    
    // Звіт по замовленнях
    public function ordersReport() {
        // Параметри періоду (за замовчуванням - поточний місяць)
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
        
        $data = [
            'title' => 'Звіт по замовленнях',
            'start_date' => $start_date,
            'end_date' => $end_date,
            'daily_stats' => $this->orderModel->getStatsByPeriod($start_date, $end_date),
            'supplier_stats' => $this->orderModel->getStatsBySupplier($start_date, $end_date),
            'material_stats' => $this->orderModel->getStatsByMaterial($start_date, $end_date)
        ];
        
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
}