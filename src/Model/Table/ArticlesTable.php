<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\ORM\Query;

class ArticlesTable extends Table
{
    public function initialize(array $config)
    {
        $this->addBehavior('Timestamp');
        $this->belongsToMany('tags');
        $this->belongsTo('users');
    }

    public function beforeSave($event, $entity, $options)
    {
        if ($entity->tag_string) {
            $entity->tags = $this->_buildTags($entity->tag_string);
        }

        if ($entity->isNew() && !$entity->slug) {
            $sluggedTitle = Text::slug($entity->title);
            $entity->slug = substr($sluggedTitle, 0, 191);
        }
    }

    protected function _buildTags($tagString)
    {
        // 追加タグの整形
        $newTags = array_map('trim', explode(',', $tagString));
        $newTags = array_filter($newTags);
        $newTags = array_unique($newTags);

        $out = [];
        // 新しいタグ名と一致するタグを取得する
        $query = $this->Tags->find()
            ->where(['Tags.title IN' => $newTags]);
        
        // 新しいタグに既存のタグと一致するものがあれば取り除く
        foreach ($query->extract('title') as $existing) {
            $index = array_search($existing, $newTags);
            if ($index !== false) {
                unset($newTags[$index]);
            }
        }

        foreach ($query as $tag) {
            $out[] = $tag;
        }

        foreach($newTags as $tag) {
            $out[] = $this->Tags->newEntity(['title' => $tag]);
        }
        
        return $out;
    }

    /**
     * Saveメソッドが呼ばれた際にデータの検証方法をCakePHPに伝える
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->allowEmptyString('title', false)
            ->minLength('title', 10)
            ->maxLength('tiyle', 255)

            ->allowEmptyString('body', false)
            ->minLength('body', 10);

        return $validator;
    }

    public function findTagged(Query $query, array $options)
    {
        $columns = [
            'Articles.id', 'Articles.user_id', 'Articles.title',
            'Articles.body', 'Articles.published', 'Articles.created',
            'Articles.slug',
        ];    

        $query = $query
            ->select($columns)
            ->distinct($columns);

        if (empty($options['tags'])) {
            $query->leftJoinWith('Tags')
                ->where(['Tags.title IS' => null]);
        } else {
            $query->innerJoinWith('Tags')
                ->where(['Tags.title IN' => $options['tags']]);
        }

        return $query->group(['Articles.id']);

    }


}