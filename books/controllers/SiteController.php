<?php

namespace app\controllers;

use app\models\Author;
use app\models\Books;
use app\models\Category;
use app\models\Circle;
use app\models\Comment;
use app\models\Favorites;
use app\models\History;
use app\models\Proposal;
use app\models\RegisterForm;
use app\models\Schedule;
use app\models\Society;
use app\models\User;
use Yii;
use yii\data\Pagination;
use yii\filters\AccessControl;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use yii\web\UploadedFile;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            if (Yii::$app->user->identity->is_admin)
            {
                return $this->redirect(['/admin']);
            } else {
                return $this->redirect(['site/index']);
            }
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    public function actionRegister()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new RegisterForm();
        if ($model->load(Yii::$app->request->post()) && $model->register()) {
            return $this->goHome();
        }

        $model->password = '';
        return $this->render('register', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }


    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        $new = Books::find()->orderBy(['date' => SORT_DESC])->limit(5)->all();
        $query = Books::find()->orderBy('date asc');
        $count = clone $query;
        $pages = new Pagination(['totalCount'=>$count->count(), 'pageSize'=>5]);

        $books = $query->offset($pages->offset)->limit($pages->limit)->all();

        $populars = Books::find()
            ->where(['>', 'viewed', 0]) // Условие: количество просмотров больше нуля
            ->orderBy(['viewed' => SORT_DESC])
            ->limit(5)
            ->all();
        $categories = Category::find()->all();
        $author = Author::find()->all();
        return $this->render('index', ['categories'=>$categories, 'populars'=>$populars, 'books'=>$books, 'new'=>$new, 'author'=>$author, 'pages'=>$pages ]);
    }

    public function actionRandomBook()
    {
        // Получаем случайный ID книги из базы данных
        $randomBookId = Yii::$app->db->createCommand('SELECT id FROM books ORDER BY RAND() LIMIT 1')->queryScalar();

        // Проверяем, что удалось получить случайный ID
        if ($randomBookId !== false) {
            // Перенаправляем пользователя на страницу случайной книги
            return $this->redirect(Url::to(['site/books', 'id' => $randomBookId]));
        } else {
            // Если случайную книгу не удалось получить, выполните необходимые действия (например, показ сообщения об ошибке)
            Yii::$app->session->setFlash('error', 'Не удалось получить случайную книгу.');
            return $this->redirect(Yii::$app->request->referrer ?: Yii::$app->homeUrl);
        }
    }

    public function actionBooks($id)
    {
        $books = Books::findOne($id);
        $user_id = Yii::$app->user->id;

        $addToFavoriteUrl = Url::to(['site/add-to-favorite', 'id' => $books->id]);
        $removeFromFavoriteUrl = Url::to(['site/remove-from-favorite', 'id' => $books->id]);

        $authorId = Yii::$app->request->get('author_id');
        $author = Author::findOne($authorId);

        // История
        // Проверим, существует ли уже запись в истории для данной книги и пользователя
        $history = History::find()
            ->where(['user_id' => $user_id, 'books_id' => $books->id])
            ->one();

        // Если записи нет, создадим новую
        if (!$history) {
            $history = new History();
            $history->user_id = $user_id;
            $history->books_id = $books->id;
        }

        // Обновим время создания записи в истории
        $history->created_at = date('Y-m-d H:i:s');
        $history->save();

        $comments = Comment::find()->where(['books_id' => $id])->all();
        $model = new Comment();

        if ($model->load(Yii::$app->request->post())) {
            $model->books_id = $books->id; // Присваиваем ID книги комментарию
            $model->user_id = $user_id;    // Присваиваем ID пользователя комментарию

            if ($model->save()) {
                return $this->refresh();
            }
        }

        $context = ['author' => $author, 'books' => $books, 'model' => $model, 'comments' => $comments,
            'addToFavoriteUrl' => $addToFavoriteUrl, 'removeFromFavoriteUrl' => $removeFromFavoriteUrl];

        return $this->render('books', $context);
    }

    public function actionAddToFavorite($id)
    {
        $user_id = Yii::$app->user->id;

        // Проверим, не существует ли уже записи для данного пользователя и книги
        $favorite = Favorites::findOne(['user_id' => $user_id, 'books_id' => $id]);

        // Если записи нет, создадим новую
        if (!$favorite) {
            $favorite = new Favorites();
            $favorite->user_id = $user_id;
            $favorite->books_id = $id;
            $favorite->save();
        }

        // Перенаправим пользователя на предыдущую страницу
        return $this->redirect(Yii::$app->request->referrer ?: Yii::$app->homeUrl);
    }

    public function actionRemoveFromFavorite($id)
    {
        $user_id = Yii::$app->user->id;

        // Находим запись в избранном для данного пользователя и книги
        $favorite = Favorites::findOne(['user_id' => $user_id, 'books_id' => $id]);

        // Если запись существует, удалим ее
        if ($favorite) {
            $favorite->delete();
        }

        // Перенаправим пользователя на предыдущую страницу
        return $this->redirect(Yii::$app->request->referrer ?: Yii::$app->homeUrl);
    }


    public function actionMy()
    {
        // Получите обновленную историю просмотра пользователя, отсортированную по времени создания в обратном порядке

        $userHistory = History::find()
            ->where(['user_id' => Yii::$app->user->id])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        // Получите все записи избранного для данного пользователя
        $favoriteBooks = Favorites::find()
            ->where(['user_id' => Yii::$app->user->id])
            ->with('books')
            ->all();

        return $this->render('my', ['userHistory' => $userHistory, 'favoriteBooks' => $favoriteBooks,]);
    }


    public function actionAuthor()
    {
        if (isset($_GET['id']) && $_GET['id']!='')
        {
            $authorId = Yii::$app->request->get('author_id');
            $author = Author::findOne($authorId);

            $books = Books::find()->where(['author_id'=>$_GET['id']])->asArray()->all();

            $context = ['author' => $author, 'books' => $books];

            return $this->render('author', $context);
        }
        else
            return $this->redirect(['author']);
    }

    public function actionMycategory()
    {
        if (isset($_GET['id']) && $_GET['id']!='')
        {
            $categories = Category::find()->where(['id'=>$_GET['id']])->asArray()->one();

            $books = Books::find()->where(['category_id'=>$_GET['id']])->with('author')->asArray()->all();

            $context = ['categories' => $categories, 'books' => $books];

            return $this->render('mycategory', $context);
        }
        else {
            return $this->redirect(['mycategory']);
        }
    }

    public function actionCategory()
    {
        $categories = Category::find()->all();
        $context = ['categories'=>$categories];
        return $this->render('category', $context);
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */

    public function actionProposal()
    {
        $model = new Proposal();
        if ($model->load(Yii::$app->request->post()) && $model->save()){
            Yii::$app->session->setFlash('success', 'Отправлено');
            $model->image = UploadedFile::getInstance($model, 'image');
            if ($model->upload()){
                $model->save();
                return $this->refresh();
            }

            return $this->refresh();
        }
        return $this->render('proposal', [
            'model' => $model,
        ]);
    }

    public function actionKabinet()
    {
        $userId = Yii::$app->user->id;
        $user = User::findOne($userId);

        $proposal = Proposal::find()->where(['user_id' => $userId])->all();

        return $this->render('kabinet', ['proposal' => $proposal, 'user'=>$user]);
    }

    public function actionAbout()
    {
        return $this->render('about');
    }
}
