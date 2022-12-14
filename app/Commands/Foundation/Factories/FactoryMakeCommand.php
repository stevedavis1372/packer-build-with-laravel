<?php

namespace App\Commands\Foundation\Factories;

use Illuminate\Support\Str;
use App\Commands\Helpers\PackageDetail;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class FactoryMakeCommand extends GeneratorCommand
{
    use PackageDetail;
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:factory';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new model factory';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Factory';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('legacy')) {
            return __DIR__ . '/stubs/factory_legacy.stub';
        }
        return __DIR__ . '/stubs/factory.stub';
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $namespaceModel = $this->option('model')
                        ? $this->qualifyClass($this->option('model'))
                        : 'Model';

        if ($this->option('legacy')) {
            return str_replace(
                'DummyModel',
                $namespaceModel,
                parent::buildClass($name)
            );
        }

        $model = class_basename($namespaceModel);

        if (Str::startsWith($namespaceModel, 'App\\Models')) {
            $namespace = Str::beforeLast('Database\\Factories\\' . Str::after($namespaceModel, 'App\\Models\\'), '\\');
        } else {
            $namespace = $this->rootNamespace() . 'Database\\Factories';
        }

        $replace = [
            '{{ factoryNamespace }}' => $namespace,
            'NamespacedDummyModel'   => $namespaceModel,
            '{{ namespacedModel }}'  => $namespaceModel,
            '{{namespacedModel}}'    => $namespaceModel,
            'DummyModel'             => $model,
            '{{ model }}'            => $model,
            '{{model}}'              => $model,
        ];

        return str_replace(
            array_keys($replace),
            array_values($replace),
            parent::buildClass($name)
        );
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);
        $path = getcwd() . $this->devPath() . '/src/';
        return $path . '/database/factories/' . str_replace('\\', '/', $name) . '.php';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'The name of the model'],
            ['legacy', 'l'],
        ];
    }
}
