<?php

namespace WF\Parental\Commands;

use Gnugat\NomoSpaco\File\FileRepository;
use Gnugat\NomoSpaco\FqcnRepository;
use Gnugat\NomoSpaco\Token\ParserFactory;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use WF\Parental\HasChildren;
use WF\Parental\HasParent;

class DiscoverChildren extends Command
{
    protected $signature = 'parental:discover-children';

    protected $description = 'Discover the child models of parent classes using the HasChildren trait.';

    public function handle()
    {
        $children = $this->findChildren();

        file_put_contents(
            $this->path(),
            '<?php'.PHP_EOL.PHP_EOL.'return '.var_export($children, true).';'.PHP_EOL
        );

        $count = count(Arr::flatten($children));

        $this->output->writeln("Parental: Successfully discovered {$count} child classes!");

        return true;
    }

    private function path()
    {
        $path = config('parental.discovered_children_path');
        $root = config('filesystems.disks.local.root');

        if (Str::startsWith($path, $root)) {
            $path = ltrim(Str::replaceFirst($root, '', $path), '/');
        }

        return $path;
    }

    private function findChildren()
    {
        $children = [];

        $repository = new FqcnRepository(new FileRepository, new ParserFactory);

        foreach (config('parental.model_directories', []) as $directory) {
            foreach ($repository->findIn($directory) as $class) {
                try {
                    if (! is_a($class, Model::class, true)) {
                        continue;
                    }

                    $traits = class_uses_recursive($class);

                    if (in_array(HasParent::class, $traits) && // It's a child
                        in_array(HasChildren::class, $traits)  // and the parent has the parent trait
                    ) {
                        $parent = get_parent_class($class);
                        $children[$parent][$class] = $class;
                    }
                } catch (\Throwable) {
                }
            }
        }

        return $children;
    }
}
