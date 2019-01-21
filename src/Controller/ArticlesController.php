<?php 

namespace App\Controller;

use Cake\ORM\TableRegistry;



class ArticlesController extends AppController
{
    public function initialize() 
    {
        parent::initialize();
        $this->loadComponent('Paginator');
        $this->Auth->allow(['tags']);
        // $this->loadComponent('Flash');
    }

    public function isAuthorized($user)
    {
        $action = $this->request->params['action'];
        // The add and tags actions are always allowed to logged in users.
        if (in_array($action, ['index', 'add', 'tags'])) {
            return true;
        }

        // if (empty($this->request->params['pass.0'])) {
        //     return false;
        // }
        // All other actions require a slug.
        $slug = $this->request->getParam('pass.0');
        debug($slug);
        if (!$slug) {
            return false;
        }

        // Check that the article belongs to the current user.
        $article = $this->Articles->findBySlug($slug)->first();
        debug($article->user_id);
        debug($user['id']);
        return $article->user_id === $user['id'];
    }

    public function index()
    {
        // debug($this->Articles->find());
        // debug($articles = TableRegistry::get('Articles')->find());
        $this->loadComponent('Paginator');
        $articles = $this->Paginator->paginate($this->Articles->find());
        //set allows to scope $this->articles to view and etc...
        $this->set(compact('articles'));
    }

    public function view($slug = null) 
    {
        $article = $this->Articles->findBySlug($slug)->firstOrFail();
        $this->set(compact('article'));
    }

    public function add() 
    {
        $article = $this->Articles->newEntity();
        if ($this->request->is('post')) {
            $article = $this->Articles->patchEntity($article, $this->request->getData());
            debug($this->request->getData());
            // Hardcoding the user_id is temporary, and will be removed later
            // when we build authentication out.
            // $article->user_id = 1;
            $article->user_id = $this->Auth->user('id');

            //  debug($article);
            if ($this->Articles->save($article)) {
                $this->Flash->success(__('Your article has been saved.'));
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('Unable to add your article.'));
        }
        $tags = $this->Articles->Tags->find('list');
        $this->set('tags', $tags);
        $this->set('article', $article);
    }

    public function edit($slug)
    {
        $article = $this->Articles
            ->findBySlug($slug)
            ->contain('Tags')
            ->firstOrFail();
        if ($this->request->is(['post', 'put'])) {
            $this->Articles->patchEntity(
                $article,
                $this->request->getData(), [
                    'accessibleFields' => ['user_id' => false]
                ]
            );
            $article->user_id = $this->Auth->user('id');
            // debug($article);

            if ($this->Articles->save($article)) {
                $this->Flash->success(__('Your article has been updated.'));
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('Unable to update your article.'));
        }
        $tags = $this->Articles->Tags->find('list');
        $this->set('tags', $tags);
        $this->set('article', $article);
    }

    public function delete($slug)
    {
        $this->request->allowMethod(['post', 'delete']);

        $article = $this->Articles->findBySlug($slug)->firstOrFail();
        if ($this->Articles->delete($article)) {
            $this->Flash->success(__('The {0} article has been deleted.', $article->title));
            return $this->redirect(['action' => 'index']);
        }
    }

    public function tags(...$tags)
    {
        // Use the ArticlesTable to find tagged articles.
        debug($tags);

        $articles = $this->Articles->find('tagged', [
            'tags' => $tags
        ]);

        // Pass variables into the view template context.
        $this->set(compact('articles', 'tags'));
    }

}