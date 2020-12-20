<?php
namespace App\Controller;

use Cake\ORM\TableRegistry;
use App\Controller\AppController;
use Cake\Database\Expression\QueryExpression;
use Cake\ORM\Query;

class DatabasesController extends AppController
{
    public function index()
    {
        $Articles = TableRegistry::getTableLocator()->get('Articles');
        // resultSetオブジェクトが返却される。
        $resultAll = $Articles->find()
            ->where(['id' => 1])
            ->all();
        
        // コレクションでできることはQueryにもできる。その逆も。
        $resultExtract = $allTitles = $Articles->find()->extract('title');

        // エンティティが返却される
        $first = $Articles
            ->find()
            ->where(['id' => 1])
            ->first();

        
        $count = $Articles->find();
        $count->select(['count' => $count->func()->count('*')])
            ->where(['id' => 1])
            ->execute();
        
        // エンティティではなく、配列を返却する
        $query = $Articles->find();
        $query->enableHydration(false);
        $result_set = $query->toList();

        // OR句の生成
        $where_query = $Articles->find()
            ->where([
               'user_id' => 1,
               'OR' => [['published' => 1], ['number' => 3]],
            ]);

        // expressionオブジェクトを使ったLIKE検索
        $exp_query = $Articles->find()
                ->where(function (QueryExpression $exp, Query $q) {
                    return $exp->like('title', '%Article%');
                });
        

        // クエリオブジェクトに対してCollectionのメソッドが使える
        $collection_method_query = $Articles->find();
        $ids = $collection_method_query->map(function ($row) {
            return $row->id;
        });

        // containによる関連づくデータのイーガーロード
        $contain_query = $Articles->find()
            ->where(['Articles.user_id' => 1])
            ->contain(['users'])
            ->toArray();
        
        // containに条件を指定する
        $contain_condition_query = $Articles->find()
            ->where(['Articles.id' => 1])
            ->contain('Tags', function (Query $q) {
                return $q
                    ->select(['id', 'title'])
                    ->where(['Tags.id' => 1]);
            })
            ->toArray();
        
        // matchingによるArticlesのフィルタリング
        $matching_query = $Articles->find()
            ->matching('Tags', function ($q) {
                return $q->where(['Tags.title' => 'tag1']);
            });
        
        // innerJoinWithを使えば関連テーブルのデータが結果セットに含まれなくなる
        $inner_join_query = $Articles->find()
            ->innerJoinWith('Tags', function ($q) {
                return $q->where(['Tags.title' => 'tag1']);
            });
    }

}

?>