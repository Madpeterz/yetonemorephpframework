<?php

namespace YAPF\Framework\Generator;

class SetModelFactory extends SingleModelFactory
{
    protected function createModelHeader(): void
    {
        $this->fileLines[] = '<?php';
        $this->fileLines[] = '';
        $this->fileLines[] = 'namespace ' . $this->namespaceSet . ';';
        $this->fileLines[] = '';
        $this->fileLines[] = 'use YAPF\Framework\Responses\DbObjects\SetsLoadReply as SetsLoadReply;';
        $this->fileLines[] = 'use YAPF\Framework\DbObjects\CollectionSet\CollectionSet as CollectionSet;';
        $this->fileLines[] = 'use YAPF\Framework\Responses\DbObjects\UpdateReply as UpdateReply;';
        $this->fileLines[] = 'use ' . $this->namespaceSingle . '\\'
        . $this->className . ' as ' . $this->className . ';';
        $this->fileLines[] = '';
        $this->fileLines[] = '// Do not edit this file, rerun gen.php to update!';
        $this->fileLines[] = 'class ' . $this->className . 'Set extends CollectionSet';
        $this->fileLines[] = '{';
        $this->fileLines[] = [1];
        $this->fileLines[] = 'public function __construct()';
        $this->fileLines[] = '{';
        $this->fileLines[] = [2];
        $this->fileLines[] = 'parent::__construct("' . $this->namespaceSingle . '\\' . $this->className . '");';
        $this->fileLines[] = [1];
        $this->fileLines[] = '}';
        $this->fileLines[] = [1];
    }

    protected function createModelDataset(): void
    {
        // Not used
    }

    protected function createModelSetters(): void
    {
        // Not used
    }

    protected function createModelGetters(): void
    {
        $this->fileLines[] = '/**';
        $this->fileLines[] = ' * getObjectByID';
        $this->fileLines[] = ' * returns a object that matchs the selected id';
        $this->fileLines[] = ' * returns null if not found';
        $this->fileLines[] = ' * Note: Does not support bad Ids please use findObjectByField';
        $this->fileLines[] = ' */';
        $this->fileLines[] = 'public function getObjectByID($id): ?' . $this->className . '';
        $this->fileLines[] = '{';
        $this->fileLines[] = [2];
        $this->fileLines[] = 'return parent::getObjectByID($id);';
        $this->fileLines[] = [1];
        $this->fileLines[] = '}';
        $this->fileLines[] = '/**';
        $this->fileLines[] = ' * getFirst';
        $this->fileLines[] = ' * returns the first object in a collection';
        $this->fileLines[] = ' */';
        $this->fileLines[] = 'public function getFirst(): ?' . $this->className . '';
        $this->fileLines[] = '{';
        $this->fileLines[] = [2];
        $this->fileLines[] = 'return parent::getFirst();';
        $this->fileLines[] = [1];
        $this->fileLines[] = '}';
        $this->fileLines[] = '/**';
        $this->fileLines[] = ' * getObjectByField';
        $this->fileLines[] = ' * returns the first object in a collection that matchs the field and value checks';
        $this->fileLines[] = ' */';
        $this->fileLines[] = 'public function getObjectByField(string $fieldName, $value): ?' . $this->className . '';
        $this->fileLines[] = '{';
        $this->fileLines[] = [2];
        $this->fileLines[] = 'return parent::getObjectByField($fieldName, $value);';
        $this->fileLines[] = [1];
        $this->fileLines[] = '}';
        $this->fileLines[] = '/**';
        $this->fileLines[] = ' * current';
        $this->fileLines[] = ' * used by foreach to get the object should not be called directly';
        $this->fileLines[] = ' */';
        $this->fileLines[] = 'public function current(): ' . $this->className . '';
        $this->fileLines[] = '{';
        $this->fileLines[] = [2];
        $this->fileLines[] = 'return parent::current();';
        $this->fileLines[] = [1];
        $this->fileLines[] = '}';

        foreach ($this->cols as $rowTwo) {
            $useType = $this->getColType(
                $rowTwo["DATA_TYPE"],
                $rowTwo["COLUMN_TYPE"],
                $this->table,
                $rowTwo["COLUMN_NAME"]
            );
            if ($useType == "str") {
                $useType = "string";
            }
            $functionName = "unique" . ucfirst($rowTwo["COLUMN_NAME"]) . "s";
            $this->fileLines[] = '/**';
            $this->fileLines[] = ' * ' . $functionName . '';
            $this->fileLines[] = ' * returns unique values from the collection matching that field';
            $this->fileLines[] = ' * @return array<' . $useType . '>';
            $this->fileLines[] = ' */';
            $this->fileLines[] = 'public function ' . $functionName . '(): array';
            $this->fileLines[] = '{';
            $this->fileLines[] = [2];
            $this->fileLines[] = 'return parent::uniqueArray("' . $rowTwo["COLUMN_NAME"] . '");';
            $this->fileLines[] = [1];
            $this->fileLines[] = '}';
        }
    }

    protected function createModelLoaders(): void
    {
        $this->fileLines[] = "// Loaders";
        foreach ($this->cols as $rowTwo) {
            $useType = $this->getColType(
                $rowTwo["DATA_TYPE"],
                $rowTwo["COLUMN_TYPE"],
                $this->table,
                $rowTwo["COLUMN_NAME"]
            );
            if ($useType == "str") {
                $useType = "string";
            }
            $functionLoadName = 'loadBy' . ucfirst($rowTwo["COLUMN_NAME"]);

            $this->fileLines[] = '/**';
            $this->fileLines[] = ' * ' . $functionLoadName;
            $this->fileLines[] = '*/';
            $this->fileLines[] = 'public function ' . $functionLoadName . '(';
            $this->fileLines[] = [2];
            $this->fileLines[] = $useType . ' $' . $rowTwo["COLUMN_NAME"] . ', ';
            $this->fileLines[] = 'int $limit = 0, ';
            $this->fileLines[] = 'string $orderBy = "id", ';
            $this->fileLines[] = 'string $orderDir = "DESC"';
            $this->fileLines[] = [1];
            $this->fileLines[] = '): SetsLoadReply';
            $this->fileLines[] = '{';
            $this->fileLines[] = [2];
            $this->fileLines[] = 'return $this->loadOnField(';
            $this->fileLines[] = [3];
            $this->fileLines[] = '"' . $rowTwo["COLUMN_NAME"] . '", ';
            $this->fileLines[] = '$' . $rowTwo["COLUMN_NAME"] . ', ';
            $this->fileLines[] = '$limit, ';
            $this->fileLines[] = '$orderBy, ';
            $this->fileLines[] = '$orderDir';
            $this->fileLines[] = [2];
            $this->fileLines[] =  ');';
            $this->fileLines[] = [1];
            $this->fileLines[] = '}';

            $functionLoadName = 'loadFrom' . ucfirst($rowTwo["COLUMN_NAME"]) . 's';

            $this->fileLines[] = '/**';
            $this->fileLines[] = ' * ' . $functionLoadName;
            $this->fileLines[] = '*/';
            $this->fileLines[] = 'public function ' . $functionLoadName . '(array $values): SetsLoadReply';
            $this->fileLines[] = '{';
            $this->fileLines[] = [2];
            $this->fileLines[] = 'return $this->loadIndexes("' . $rowTwo["COLUMN_NAME"] . '", $values);';
            $this->fileLines[] = [1];
            $this->fileLines[] = '}';
        }
    }

    protected function createRelatedLoaders(): void
    {
        $this->fileLines[] = '// Related loaders';
        $this->seenRelated = [];
        foreach ($this->links as $entry) {
            $targetClass = "";
            $fromField = "";
            $loadField = "";
            if ($entry["source_table"] == $this->table) {
                $targetClass = ucfirst(strtolower($entry["target_table"]));
                $fromField = ucfirst($entry["source_field"]);
                $loadField = ucfirst($entry["targetField"]);
            } elseif ($entry["target_table"] == $this->table) {
                $targetClass = ucfirst(strtolower($entry["source_table"]));
                $fromField = ucfirst($entry["targetField"]);
                $loadField = ucfirst($entry["source_field"]);
            }
            if ($targetClass == "") {
                continue;
            }

            $targetClassName =  $targetClass . "Set";
            if (in_array($targetClassName, $this->seenRelated) == true) {
                continue;
            }

            $this->seenRelated[] = $targetClassName;
            $this->fileLines[] = 'public function related' . $targetClass . '(): ' . $targetClassName . '';
            $this->fileLines[] = '{';
            $this->fileLines[] = [2];
            $this->fileLines[] = '$ids = $this->unique' . $fromField . 's();';
            $this->fileLines[] = '$collection = new ' . $targetClassName . '();';
            $this->fileLines[] = '$collection->loadFrom' . $loadField . 's($ids);';
            $this->fileLines[] = 'return $collection;';
            $this->fileLines[] = [1];
            $this->fileLines[] = '}';
        }
    }

    protected function createModelFooter(): void
    {
        $this->fileLines[] = [0];
        $this->fileLines[] = '}';
    }
}
