<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

trait SyncHasManyRelation
{
    /**
     * 同步 hasMany 关系（增、改、删）
     *
     * @param string $relationName 关联关系方法名
     * @param array $items 前端传入的关联数据数组
     * @param array|null $fillable 限定允许同步的字段（null 表示不限制）
     * @return void
     */
    public function syncHasMany(string $relationName, array $items, array $fillable = null): void
    {
        DB::transaction(function () use ($relationName, $items, $fillable) {
            $relation = $this->$relationName();

            if (!$relation instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                throw new \InvalidArgumentException("Relation '{$relationName}' is not a valid Eloquent relation.");
            }

            $relatedModel = $relation->getRelated();         // 子模型
            $foreignKey   = $relation->getForeignKeyName();  // 外键字段
            $parentKey    = (int) $this->getKey();          // 父模型主键
            $keepIds = [];

            foreach ($items as $item) {
                $data = $fillable ? Arr::only($item, $fillable) : $item;

                if (isset($item['id'])) {
                    // 直接用子模型查
                    $update = $relatedModel->find((int)$item['id']);
                    if ($update) {
                        $update->update($data);
                        $keepIds[] = $update->id;
                    }
                } else {
                    // 新增时带上外键
                    $data[$foreignKey] = $parentKey;
                    $new = $relation->create($data);
                    $keepIds[] = $new->id;
                }
            }

            // 当前父模型已有子项 ID
            $existingIds = $relation->pluck('id')->toArray();
            // 差集，计算需要删除的 ID
            $toDeleteIds = array_diff($existingIds, $keepIds);

            // 删除未保留的
            if (!empty($toDeleteIds)) {
                $relation->whereIn('id', $toDeleteIds)->delete();
            }
        });
    }
}
