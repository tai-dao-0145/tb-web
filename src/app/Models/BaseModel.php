<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use stdClass;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BaseModel extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = ['deleted_at', 'created_at', 'updated_at'];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = [];

    /**
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();
    }

    /**
     * Get all column name of table
     * @return array
     */
    public function getTableColumns(): array
    {
        $result = [];
        $table_columns = $this->getConnection()->getSchemaBuilder()->getColumnListing($this->getTable());

        foreach ($table_columns as $index => $column_name) {
            $column = new stdClass();
            $column->column_name = $column_name;
            $result[$index] = $column;
        }

        return $result;
    }

    /**
     * Get all column name of table
     *
     * @return array
     */
    public function getVisibleTableColumns(): array
    {
        $table_columns = $this->getConnection()->getSchemaBuilder()->getColumnListing($this->getTable());

        return array_values(array_diff($table_columns, $this->hidden));
    }

    /**
     * @return string
     */
    public function getDateFormat(): string
    {
        return 'Y-m-d H:i:s.u';
    }

    /**
     * @param $value : value
     * @return false|Carbon
     */
    protected function asDateTime($value)
    {

        // If this value is already a Carbon instance, we shall just return it as is.
        // This prevents us having to re-instantiate a Carbon instance when we know
        // it already is one, which wouldn't be fulfilled by the DateTime check.
        if ($value instanceof Carbon) {
            return $value;
        }

        // If the value is already a DateTime instance, we will just skip the rest of
        // these checks since they will be a waste of time, and hinder performance
        // when checking the field. We will just return the DateTime right away.
        if ($value instanceof DateTimeInterface) {
            return new Carbon(
                $value->format('Y-m-d H:i:s.u'), $value->getTimezone()
            );
        }

        // If this value is an integer, we will assume it is a UNIX timestamp's value
        // and format a Carbon object from this timestamp. This allows flexibility
        // when defining your date fields as they might be UNIX timestamps here.
        if (is_numeric($value)) {
            return Carbon::createFromTimestamp($value);
        }

        // If the value is in simply year, month, day format, we will instantiate the
        // Carbon instances from that format. Again, this provides for simple date
        // fields on the database, while still supporting Carbonized conversion.
        if ($this->isStandardDateFormat($value)) {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        }

        // 追加した内容
        $value = date($this->getDateFormat(), strtotime($value));

        // Finally, we will just assume this date is in the format used by default on
        // the database connection and use that format to create the Carbon object
        // that is returned back out to the developers after we convert it here.
        return Carbon::createFromFormat(
            str_replace('.v', '.u', $this->getDateFormat()), $value
        );
    }

    /**
     * @param $query
     * @return Builder
     */
    public function scopeWhereCurrentBranch($query): Builder
    {
        return $query->where('branch_id', auth()->user()->branch_id);
    }

    /**
     * @param $query
     * @return Builder
     */
    public function scopeWhereCurrentFacility($query): Builder
    {
        return $query->where('facility_id', auth()->user()->facility_id);
    }
}
