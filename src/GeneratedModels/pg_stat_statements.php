<?php

namespace Starlight93\LaravelSmartApi\GeneratedModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class pg_stat_statements extends Model
{   
    use \Starlight93\LaravelSmartApi\Traits\ModelTrait;
    protected $table    = 'pg_stat_statements';
    protected $guarded  = ['id'];
    protected $casts    = ['created_at'=> 'datetime:d-m-Y','updated_at'=>'datetime:d-m-Y'];
    protected $fillable = ["userid","dbid","toplevel","queryid","query","plans","total_plan_time","min_plan_time","max_plan_time","mean_plan_time","stddev_plan_time","calls","total_exec_time","min_exec_time","max_exec_time","mean_exec_time","stddev_exec_time","rows","shared_blks_hit","shared_blks_read","shared_blks_dirtied","shared_blks_written","local_blks_hit","local_blks_read","local_blks_dirtied","local_blks_written","temp_blks_read","temp_blks_written","shared_blk_read_time","shared_blk_write_time","local_blk_read_time","local_blk_write_time","temp_blk_read_time","temp_blk_write_time","wal_records","wal_fpi","wal_bytes","wal_buffers_full","jit_functions","jit_generation_time","jit_inlining_count","jit_inlining_time","jit_optimization_count","jit_optimization_time","jit_emission_count","jit_emission_time","jit_deform_count","jit_deform_time","parallel_workers_to_launch","parallel_workers_launched","stats_since","minmax_stats_since"];

    public $columns     = ["userid","dbid","toplevel","queryid","query","plans","total_plan_time","min_plan_time","max_plan_time","mean_plan_time","stddev_plan_time","calls","total_exec_time","min_exec_time","max_exec_time","mean_exec_time","stddev_exec_time","rows","shared_blks_hit","shared_blks_read","shared_blks_dirtied","shared_blks_written","local_blks_hit","local_blks_read","local_blks_dirtied","local_blks_written","temp_blks_read","temp_blks_written","shared_blk_read_time","shared_blk_write_time","local_blk_read_time","local_blk_write_time","temp_blk_read_time","temp_blk_write_time","wal_records","wal_fpi","wal_bytes","wal_buffers_full","jit_functions","jit_generation_time","jit_inlining_count","jit_inlining_time","jit_optimization_count","jit_optimization_time","jit_emission_count","jit_emission_time","jit_deform_count","jit_deform_time","parallel_workers_to_launch","parallel_workers_launched","stats_since","minmax_stats_since"];
    public $columnsFull = ["userid:string","dbid:string","toplevel:string","queryid:string","query:string","plans:string","total_plan_time:string","min_plan_time:string","max_plan_time:string","mean_plan_time:string","stddev_plan_time:string","calls:string","total_exec_time:string","min_exec_time:string","max_exec_time:string","mean_exec_time:string","stddev_exec_time:string","rows:string","shared_blks_hit:string","shared_blks_read:string","shared_blks_dirtied:string","shared_blks_written:string","local_blks_hit:string","local_blks_read:string","local_blks_dirtied:string","local_blks_written:string","temp_blks_read:string","temp_blks_written:string","shared_blk_read_time:string","shared_blk_write_time:string","local_blk_read_time:string","local_blk_write_time:string","temp_blk_read_time:string","temp_blk_write_time:string","wal_records:string","wal_fpi:string","wal_bytes:string","wal_buffers_full:string","jit_functions:string","jit_generation_time:string","jit_inlining_count:string","jit_inlining_time:string","jit_optimization_count:string","jit_optimization_time:string","jit_emission_count:string","jit_emission_time:string","jit_deform_count:string","jit_deform_time:string","parallel_workers_to_launch:string","parallel_workers_launched:string","stats_since:string","minmax_stats_since:string"];
    public $rules       = "[]";
    public $joins       = [];
    public $details     = [];
    public $heirs       = [];
    public $detailsChild= [];
    public $detailsHeirs= [];
    public $unique      = [];
    public $required    = '[]';
    public $createable  = ["userid","dbid","toplevel","queryid","query","plans","total_plan_time","min_plan_time","max_plan_time","mean_plan_time","stddev_plan_time","calls","total_exec_time","min_exec_time","max_exec_time","mean_exec_time","stddev_exec_time","rows","shared_blks_hit","shared_blks_read","shared_blks_dirtied","shared_blks_written","local_blks_hit","local_blks_read","local_blks_dirtied","local_blks_written","temp_blks_read","temp_blks_written","shared_blk_read_time","shared_blk_write_time","local_blk_read_time","local_blk_write_time","temp_blk_read_time","temp_blk_write_time","wal_records","wal_fpi","wal_bytes","wal_buffers_full","jit_functions","jit_generation_time","jit_inlining_count","jit_inlining_time","jit_optimization_count","jit_optimization_time","jit_emission_count","jit_emission_time","jit_deform_count","jit_deform_time","parallel_workers_to_launch","parallel_workers_launched","stats_since","minmax_stats_since"];
    public $updateable  = ["userid","dbid","toplevel","queryid","query","plans","total_plan_time","min_plan_time","max_plan_time","mean_plan_time","stddev_plan_time","calls","total_exec_time","min_exec_time","max_exec_time","mean_exec_time","stddev_exec_time","rows","shared_blks_hit","shared_blks_read","shared_blks_dirtied","shared_blks_written","local_blks_hit","local_blks_read","local_blks_dirtied","local_blks_written","temp_blks_read","temp_blks_written","shared_blk_read_time","shared_blk_write_time","local_blk_read_time","local_blk_write_time","temp_blk_read_time","temp_blk_write_time","wal_records","wal_fpi","wal_bytes","wal_buffers_full","jit_functions","jit_generation_time","jit_inlining_count","jit_inlining_time","jit_optimization_count","jit_optimization_time","jit_emission_count","jit_emission_time","jit_deform_count","jit_deform_time","parallel_workers_to_launch","parallel_workers_launched","stats_since","minmax_stats_since"];
    public $searchable  = ["userid","dbid","toplevel","queryid","query","plans","total_plan_time","min_plan_time","max_plan_time","mean_plan_time","stddev_plan_time","calls","total_exec_time","min_exec_time","max_exec_time","mean_exec_time","stddev_exec_time","rows","shared_blks_hit","shared_blks_read","shared_blks_dirtied","shared_blks_written","local_blks_hit","local_blks_read","local_blks_dirtied","local_blks_written","temp_blks_read","temp_blks_written","shared_blk_read_time","shared_blk_write_time","local_blk_read_time","local_blk_write_time","temp_blk_read_time","temp_blk_write_time","wal_records","wal_fpi","wal_bytes","wal_buffers_full","jit_functions","jit_generation_time","jit_inlining_count","jit_inlining_time","jit_optimization_count","jit_optimization_time","jit_emission_count","jit_emission_time","jit_deform_count","jit_deform_time","parallel_workers_to_launch","parallel_workers_launched","stats_since","minmax_stats_since"];
    public $deleteable  = true;
    public $cascade     = true;
    public $deleteOnUse = false;

    
    
    
}
