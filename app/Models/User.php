<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Role as SpatieRole;
use App\Models\DepartmentApprover;
use App\Models\Worker;
use App\Models\LeaderAssignment;


class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'payroll_number',
        'phone_number',
        'avatar',
        'email',
        'password',
        'role_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /* ==========================================================
     |  RELATIONSHIPS
     |==========================================================*/

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function worker()
    {
        return $this->hasOne(Worker::class, 'user_id', 'id');
    }

    public function departmentApprover()
    {
        return $this->hasMany(DepartmentApprover::class, 'approver_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }



    /* ==========================================================
     |  ACCESSORS & HELPERS
     |==========================================================*/

    /**
     * Mendapatkan URL avatar (foto profil).
     * Jika kosong, akan menampilkan avatar default.
     */
    public function getFilamentAvatarUrl(): ?string
    {
        if ($this->avatar && file_exists(storage_path('app/public/' . $this->avatar))) {
            return asset('storage/' . $this->avatar);
        }

        // fallback default (UI Avatar)
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=FFFFFF&background=09090b';
    }

    /**
     * Mendapatkan level approver user dalam departemen tertentu.
     */
    public function approverLevel($departmentId): int
    {
        return DepartmentApprover::where('department_id', $departmentId)
            ->where('approver_id', $this->id)
            ->value('level') ?? 0;
    }

    /* ==========================================================
     |  BOOT METHODS
     |==========================================================*/

    public function syncRolesAndUpdateId(array|string $roles, $guardName = null): void
    {
        // Panggil method trait asli
        $this->syncRoles($roles, $guardName);

        // Ambil role pertama jika multiple
        $roleName = is_array($roles) ? $roles[0] : $roles;

        $role = Role::where('name', $roleName)->first();
        if ($role) {
            $this->role_id = $role->id;
            $this->saveQuietly(); // simpan tanpa memicu observer
        }
    }

     protected static function booted()
    {
        // Hapus file lama jika avatar diubah
        static::updating(function ($user) {
            if ($user->isDirty('avatar') && $user->getOriginal('avatar')) {
                Storage::disk('public')->delete($user->getOriginal('avatar'));
            }

            // Sinkron role Spatie jika role_id berubah
            if ($user->isDirty('role_id')) {
                $role = SpatieRole::find($user->role_id);
                if ($role) {
                    $user->syncRoles($role->name);
                }
            }
        });

        // Hapus file avatar ketika user dihapus
        static::deleting(function ($user) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
        });
    }

    /* ==========================================================
     |  CUSTOM UPLOAD HANDLER (mengikuti mekanisme dokumen cuti)
     |==========================================================*/

    /**
     * Simpan avatar menggunakan metode yang seragam dengan dokumen cuti.
     */
    public static function storeAvatar($file): ?string
    {
        if (!$file) {
            return null;
        }

        // Sama seperti mekanisme upload dokumen cuti
        return $file->store('profile_photos', 'public');
    }

    // User yang menjadi leader dari banyak worker
    public function subordinates()
    {
        return $this->hasMany(LeaderAssignment::class, 'leader_id')->whereNull('ended_at');
    }

    // User yang memiliki atasan (leader)
    // leader_assignments.worker_id references the workers table (workers.id).
    // Provide a hasManyThrough relation so callers get a proper Relation instance
    // that can be chained and queried like other Eloquent relations.
    public function leaderAssignments()
    {
        return $this->hasManyThrough(
            LeaderAssignment::class,
            Worker::class,
            'user_id',      // workers.user_id -> local key on workers
            'worker_id',    // leader_assignments.worker_id -> foreign key on leader_assignments
            'id',           // local key on users
            'id'            // local key on workers
        )->whereNull('ended_at');
    }

    // Helper untuk mengambil atasan aktif
    public function currentLeader()
    {
        return $this->leaderAssignments()
            ->with('leader')
            ->active()
            ->latest('effective_date')
            ->first()
            ?->leader;
    }

    // Helper untuk mengambil semua bawahan aktif
    public function currentSubordinates()
    {
        return $this->subordinates()
            ->with('worker')
            ->active()
            ->get()
            ->pluck('worker');
    }

    /**
     * Helper: get the worker id associated with this user (if any).
     * Use this method when you need the worker primary key from a User instance.
     */
    public function getWorkerId(): ?int
    {
        return $this->worker?->id;
    }

    /**
     * Helper: get worker id for this user (if any).
     * Usage: $user->workerId() or $user->worker_id (dynamic attribute)
     */
    public function workerId(): ?int
    {
        return $this->worker?->id;
    }

    // Dynamic accessor so $user->worker_id is available and returns worker id when present
    public function getWorkerIdAttribute(): ?int
    {
        return $this->worker?->id;
    }
}
