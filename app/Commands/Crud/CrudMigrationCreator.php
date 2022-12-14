<?php

namespace App\Commands\Crud;

use Illuminate\Filesystem\Filesystem;
use App\Commands\Helpers\PackageDetail;
use Illuminate\Database\Migrations\MigrationCreator as RealMigrationCreator;

class CrudMigrationCreator extends RealMigrationCreator
{
    use PackageDetail;

    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    /**
     * Get the full path to the migration.
     *
     * @param  string  $name
     * @param  string  $path
     * @return string
     */
    protected function getPath($name, $path)
    {
        $path = getcwd() . $this->devPath();
        $path = $this->getComposer()->type === 'library' ? $path . 'src/' : $path;
        $path = $path . 'database/migrations';

        if (!$this->files->isDirectory($path)) {
            $this->files->makeDirectory($path, 0777, true);
        }

        return $path . '/' . $this->getDatePrefix() . '_' . $name . '.php';
    }

    public function create($name, $path, $table = null, $create = false)
    {
        $this->ensureMigrationDoesntAlreadyExist($name);

        // First we will get the stub file for the migration, which serves as a type
        // of template for the migration. Once we have those we will populate the
        // various place-holders, save the file, and run the post create event.
        $stub = $this->addFieldsToStub($table, $create);

        $this->files->put(
            $path = $this->getPath($name, $path),
            $this->populateStub($name, $stub, $table)
        );

        // Next, we will fire any hooks that are supposed to fire after a migration is
        // created. Once that is done we'll be ready to return the full path to the
        // migration file so it can be used however it's needed by the developer.
        $this->firePostCreateHooks($table);

        return $path;
    }

    public function addFieldsToStub($table, $create)
    {
        $stub    = $this->getStub($table, $create);
        $fields  = cache()->get('structure')->fields;
        $newLine = '';
        foreach ($fields as $field) {
            $index           = isset($field->index) ? '->index()' : '';
            $nullable        = isset($field->nullable) ? '->nullable()' : '';
            $def             = isset($field->def) ? "->default('{$field->def}')" : '';
            $length          = isset($field->length) ? ",{$field->length}" : '';
            $newLine .= '
            $table->' . "{$field->type}('{$field->name}'$length){$index}{$def}{$nullable};";
            // $table->' . $field->type . '(\'' . $field->name . '\');';
        }
        $to_replace   = '$table->id();';
        $replace_with = $to_replace . $newLine;
        $result       = str_replace($to_replace, $replace_with, $stub);
        return $result;
    }
}
