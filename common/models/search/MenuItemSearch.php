<?php

namespace common\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\MenuItem;

/**
 * MenuItemSearch represents the model behind the search form about `common\models\MenuItem`.
 */
class MenuItemSearch extends MenuItem
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'parent_id', 'sort_order'], 'integer'],
            [['label', 'url', 'icon', 'icon_type', 'location', 'target', 'heading', 'visible', 'only_developers', 'created_at', 'updated_at', 'visible_to_roles'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = MenuItem::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'label', $this->label])
            ->andFilterWhere(['like', 'url', $this->url])
            ->andFilterWhere(['like', 'icon', $this->icon])
            ->andFilterWhere(['like', 'icon_type', $this->icon_type])
            ->andFilterWhere(['like', 'location', $this->location])
            ->andFilterWhere(['like', 'target', $this->target])
            ->andFilterWhere(['like', 'heading', $this->heading])
            ->andFilterWhere(['like', 'visible', $this->visible])
            ->andFilterWhere(['like', 'only_developers', $this->only_developers])
            ->andFilterWhere(['like', 'visible_to_roles', $this->visible_to_roles]);

        return $dataProvider;
    }
}
