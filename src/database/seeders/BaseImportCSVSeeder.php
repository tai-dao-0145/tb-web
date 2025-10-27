<?php

namespace Database\Seeders;

use App\Helpers\LogHelperService;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class BaseImportCSVSeeder
 */
abstract class BaseImportCSVSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     *
     * @throws Exception
     */
    public function run()
    {
        try {
            $this->processInsert($this->getPathFileCSV(), $this->getTableName());
        } catch (Exception $exception) {
            app(LogHelperService::class)->error('File ['.$this->getTableName().']: '.$exception->getMessage());
            if (config('app.env') === 'testing') {
                throw $exception;
            }
            exit();
        }
    }

    /**
     * processInsert
     *
     * @param $path_file_csv : path_file_csv
     * @param $table         : table
     * @return void
     *
     * @throws Exception
     */
    private function processInsert($file_csv, $table)
    {
        $path_file_csv = dirname(__FILE__)."/csv/$file_csv";
        if (! file_exists($path_file_csv)) {
            return;
        }
        $columns = Schema::getConnection()->getDoctrineSchemaManager()->listTableColumns($table) ?? [];

        $file = fopen($path_file_csv, 'r');
        $row = 0;
        $row_header = 0;
        $data_header = [];
        try {
            if (config('app.env') === 'testing') {
                DB::unprepared('ALTER TABLE '.$table.' DISABLE TRIGGER all');
            }

            while (! feof($file)) {
                $data_row = fgetcsv($file);
                if ($row == $row_header) {
                    if (in_array('id', $data_row)) {
                        $data_header = $data_row;
                    } else {
                        $data = $this->convertToDataInsert($data_header, $columns, $data_row);
                        if (count($data) != 0) {
                            DB::table($table)->insert($data);
                        }
                    }
                } elseif ($row > $row_header) {
                    $data = $this->convertToDataInsert($data_header, $columns, $data_row);
                    if (count($data) != 0) {
                        DB::table($table)->insert($data);
                    }
                }
                $row++;
            }
            fclose($file);
        } finally {
            if (config('app.env') === 'testing') {
                DB::unprepared('ALTER TABLE '.$table.' ENABLE TRIGGER all');
            }
        }
    }

    /**
     * convertToDataInsert
     *
     * @param  array  $list_column      : list_column will insert into database
     * @param  array  $list_column_type : list type of column in database
     * @param  array|bool  $data_row         : data in file csv (1 row)
     */
    private function convertToDataInsert(array $list_column = [], array $list_column_type = [], $data_row = false): array
    {
        if (! $data_row) {
            return [];
        }
        $data = [];
        if (count($list_column) != 0) {
            if (count($list_column) !== count($data_row)) {
                throw new Exception(
                    'Data not enough:'
                    .' [Length column in DB - '.count($list_column).'] ,'
                    .' [Data in csv - '.count($data_row).']'
                    .' [Data in row - '.json_encode($data_row).']'
                );
            }
            foreach ($list_column as $index => $column) {
                $column = trim($column);
                $key = $column;
                if ($key == 'order') {
                    $key = '"order"';
                }
                if (isset($list_column_type[$key])) {
                    $data[$column] = $this->convertToType(
                        $data_row[$index],
                        $list_column_type[$key]->getType()->getName()
                    );
                } else {
                    throw new Exception('This column not exist: '.$column);
                }
            }
        } else {
            if (count($list_column_type) !== count($data_row)) {
                throw new Exception(
                    'Data not enough:'
                    .' [Length column in DB - '.count($list_column_type).'] ,'
                    .' [Data in csv - '.count($data_row).']'
                    .' [Data in row - '.json_encode($data_row).']'
                );
            }
            $index = 0;
            foreach ($list_column_type as $key => $column_type) {
                $column = $key;
                if ($column == '"order"') {
                    $column = 'order';
                }
                $data[$column] = $this->convertToType($data_row[$index], $column_type->getType()->getName());
                $index++;
            }
        }

        return $data;
    }

    /**
     * convertToType
     *
     * @param  mixed|null  $data : data
     * @param  string|null  $type : type in database (int8,varchar,bool,timestamp...)
     * @return Carbon|mixed|null
     *
     * @throws Exception
     */
    private function convertToType($data = null, $type = null)
    {
        if ($type === null) {
            return $data;
        }
        if ($data === null) {
            return $data;
        }
        if ($data === '\N') {
            return null;
        }
        switch ($type) {
            case 'time':
            case 'date':
            case 'datetime':
                if ($data === '') {
                    throw new Exception('Datetime can\'t be empty');
                }

                return Carbon::parse($data);
            case 'smallint':
            case 'bigint':
            case 'integer':
            case 'float':
            case 'text':
            case 'string':
            case 'json':
            case 'boolean':
                return $data;
            default:
                throw new Exception('Type undefined: '.$type);
        }
    }

    /**
     * getPathFileCSV
     *
     * @return string
     */
    abstract public function getPathFileCSV();

    /**
     * getTableName
     *
     * @return string
     */
    abstract public function getTableName();
}
