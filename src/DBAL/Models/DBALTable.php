<?php

namespace KitLoong\MigrationsGenerator\DBAL\Models;

use Doctrine\DBAL\Schema\Column as DoctrineDBALColumn;
use Doctrine\DBAL\Schema\Index as DoctrineDBALIndex;
use Doctrine\DBAL\Schema\Table as DoctrineDBALTable;
use Illuminate\Support\Collection;
use KitLoong\MigrationsGenerator\Schema\Models\Column;
use KitLoong\MigrationsGenerator\Schema\Models\Index;
use KitLoong\MigrationsGenerator\Schema\Models\Table;

abstract class DBALTable implements Table
{
    /**
     * @var string|null
     */
    protected $collation;

    /**
     * @var \Illuminate\Support\Collection<\KitLoong\MigrationsGenerator\Schema\Models\Column>
     */
    protected $columns;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $comment;

    /**
     * @var \Illuminate\Support\Collection<\KitLoong\MigrationsGenerator\Schema\Models\Index>
     */
    protected $indexes;

    /**
     * Create a new instance.
     *
     * @param  \Doctrine\DBAL\Schema\Table  $table
     * @param  array<string, \Doctrine\DBAL\Schema\Column>  $columns  Key is quoted name.
     * @param  array<string, \Doctrine\DBAL\Schema\Index>  $indexes  Key is name.
     */
    public function __construct(DoctrineDBALTable $table, array $columns, array $indexes)
    {
        $this->name      = $table->getName();
        $this->comment   = $table->getComment();
        $this->collation = $table->getOptions()['collation'] ?? null;
        $this->columns   = (new Collection($columns))->map(function (DoctrineDBALColumn $column) use ($table) {
            return $this->makeColumn($table->getName(), $column);
        })->values();
        $this->indexes   = (new Collection($indexes))->map(function (DoctrineDBALIndex $index) use ($table) {
            return $this->makeIndex($table->getName(), $index);
        })->values();

        $this->handle();
    }

    /**
     * Instance extend this abstract may run special handling.
     *
     * @return void
     */
    abstract protected function handle(): void;

    /**
     * Make a Column instance.
     *
     * @param  string  $table
     * @param  \Doctrine\DBAL\Schema\Column  $column
     * @return \KitLoong\MigrationsGenerator\Schema\Models\Column
     */
    abstract protected function makeColumn(string $table, DoctrineDBALColumn $column): Column;

    /**
     * Make an Index instance.
     *
     * @param  string  $table
     * @param  \Doctrine\DBAL\Schema\Index  $index
     * @return \KitLoong\MigrationsGenerator\Schema\Models\Index
     */
    abstract protected function makeIndex(string $table, DoctrineDBALIndex $index): Index;

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function getColumns(): Collection
    {
        return $this->columns;
    }

    /**
     * @inheritDoc
     */
    public function getIndexes(): Collection
    {
        return $this->indexes;
    }

    /**
     * @inheritDoc
     */
    public function getCollation(): ?string
    {
        return $this->collation;
    }

    /**
     * @inheritDoc
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }
}
