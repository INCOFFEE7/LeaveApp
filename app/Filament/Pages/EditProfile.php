<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Filament\Facades\Filament;


class EditProfile extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static string $view = 'filament.pages.edit-profile';
    protected static ?string $title = 'Edit Profil';
    protected static ?string $navigationLabel = 'Edit Profil';
    protected static ?string $slug = 'edit-profile';
    protected static ?int $navigationSort = 9999; // posisi paling bawah sidebar

    public ?array $data = [];
    public ?string $avatarPreview = null;

    public function mount(): void
    {
        $user = Auth::user();

        $this->form->fill([
            'name'  => $user->name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
        ]);

        $this->avatarPreview = $user->avatar
            ? asset('storage/' . $user->avatar)
            : null;
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nama Lengkap')
                ->required(),

            Forms\Components\TextInput::make('email')
                ->label('Email')
                ->email()
                ->required(),

            Forms\Components\TextInput::make('phone_number')
                ->label('Nomor Telepon')
                ->tel(),

            Forms\Components\FileUpload::make('avatar')
                ->label('Foto Profil')
                ->image()
                ->disk('public')
                ->directory('profile_photos')
                ->visibility('public')
                ->maxSize(2048)
                ->preserveFilenames()
                ->imagePreviewHeight('150')
                ->openable()
                ->downloadable(),

            Forms\Components\TextInput::make('password')
                ->password()
                ->label('Password Baru')
                ->dehydrateStateUsing(fn($state) => filled($state) ? Hash::make($state) : null)
                ->nullable()
                ->same('password_confirmation'),

            Forms\Components\TextInput::make('password_confirmation')
                ->password()
                ->label('Konfirmasi Password Baru')
                ->dehydrated(false),
        ])->statePath('data');
    }

    public function submit(): void
    {
        $user = Auth::user();
        $data = $this->form->getState(); // ambil data pasti dari form Filament

        // update field biasa
        $user->fill([
            'name'         => $data['name'] ?? $user->name,
            'email'        => $data['email'] ?? $user->email,
            'phone_number' => $data['phone_number'] ?? $user->phone_number,
        ]);

        // 🖼️ handle avatar baru
        if (!empty($data['avatar'])) {
            $avatar = $data['avatar'];

            // Jika masih berupa file upload sementara
            if ($avatar instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                $path = $avatar->store('profile_photos', 'public'); // simpan permanen
            } elseif (is_string($avatar)) {
                // Jika sudah berupa string path (misal edit tanpa ubah file)
                $path = $avatar;
            } else {
                $path = null;
            }

            // hapus avatar lama kalau ada
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $user->avatar = $path;
        }

        // 🔐 handle password baru
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        \Cache::flush();
        Filament::auth()->user()->refresh();
        Notification::make()
            ->title('Profil berhasil diperbarui!')
            ->success()
            ->send();

        // update tampilan
        $this->dispatch('profile-updated');

        // update avatar preview
        $this->avatarPreview = $user->avatar
            ? asset('storage/' . $user->avatar)
            : null;
    }
}
