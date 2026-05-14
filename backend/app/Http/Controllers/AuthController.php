<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => '邮箱或密码错误'], 401);
        }

        if ($user->isBanned()) {
            return response()->json([
                'message' => '你的账号已被封禁，解封时间：' . $user->banned_until->format('Y-m-d H:i'),
                'banned_until' => $user->banned_until->toIso8601String(),
                'ban_reason' => $user->ban_reason,
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $this->serializeUser($user),
        ]);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'username'              => 'required|string|max:50|unique:users,username',
            'email'                 => 'required|email|unique:users,email',
            'password'              => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'username' => $validated['username'],
            'email'    => $validated['email'],
            'password' => bcrypt($validated['password']),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $this->serializeUser($user),
        ], 201);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::where('username', $validated['username'])->first();

        if (!$user) {
            return response()->json(['message' => '用户不存在'], 404);
        }

        $user->password = bcrypt($validated['password']);
        $user->save();

        return response()->json(['message' => '密码重置成功']);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => '已退出登录']);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        if ($user->isBanned()) {
            $request->user()->currentAccessToken()->delete();
            return response()->json([
                'message' => '你的账号已被封禁，解封时间：' . $user->banned_until->format('Y-m-d H:i'),
                'banned_until' => $user->banned_until->toIso8601String(),
                'ban_reason' => $user->ban_reason,
                'force_logout' => true,
            ], 403);
        }

        return response()->json([
            'data' => $this->serializeUser($user),
            'user' => $this->serializeUser($user),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $currentBanners = collect($user->profile_banners ?? [])
            ->map(fn ($item) => $this->normalizeBannerItem($item))
            ->filter()
            ->values();
        $retainedBanners = collect($request->input('existing_profile_banners', []))
            ->map(fn ($value) => $this->normalizeBannerItem($value))
            ->filter()
            ->values();

        $validated = $request->validate([
            'username'          => "sometimes|string|max:50|unique:users,username,{$user->id}",
            'bio'               => 'sometimes|nullable|string|max:200',
            'password'          => 'sometimes|nullable|string|min:6|confirmed',
        ]);

        $currentBannerPaths = $currentBanners->pluck('path')->filter()->values();
        $retainedBannerPaths = $retainedBanners->pluck('path')->filter()->values();

        if ($retainedBannerPaths->diff($currentBannerPaths)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'existing_profile_banners' => ['包含无效的已存在背景图。'],
            ]);
        }

        if ($request->hasFile('avatar')) {
            $this->assertValidImage($request->file('avatar'), 'avatar', 4096);
        }

        if ($request->hasFile('profile_banners')) {
            $banners = $request->file('profile_banners');

            if (count($banners) > 5) {
                throw ValidationException::withMessages([
                    'profile_banners' => ['主页背景图最多上传 5 张。'],
                ]);
            }

            foreach ($banners as $banner) {
                $this->assertValidImage($banner, 'profile_banners', 6144);
            }
        }

        $incomingBannerCount = count($request->file('profile_banners', []));

        if (($retainedBannerPaths->count() + $incomingBannerCount) > 5) {
            throw ValidationException::withMessages([
                'profile_banners' => ['主页背景图最多保留 5 张。'],
            ]);
        }

        if ($request->hasFile('avatar')) {
            $this->deleteLocalUpload($user->avatar);
            $validated['avatar'] = $this->storeUploadedFile($request->file('avatar'), 'avatars');
        }

        if ($request->hasFile('profile_banners') || $request->has('existing_profile_banners')) {
            foreach ($currentBanners as $banner) {
                if (! $retainedBannerPaths->contains($banner['path'])) {
                    $this->deleteLocalUpload($banner['path']);
                }
            }

            $incomingBannerMeta = collect($request->input('profile_banners_meta', []))
                ->map(function ($value) {
                    if (is_string($value)) {
                        $decoded = json_decode($value, true);
                        $value = json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : [];
                    }

                    if (! is_array($value)) {
                        return [];
                    }

                    return [
                        'focusX' => isset($value['focusX']) ? max(0, min(100, (float) $value['focusX'])) : 50,
                        'focusY' => isset($value['focusY']) ? max(0, min(100, (float) $value['focusY'])) : 50,
                        'zoom' => isset($value['zoom']) ? max(1, min(2.5, (float) $value['zoom'])) : 1,
                    ];
                })
                ->values();

            $uploadedBanners = collect($request->file('profile_banners', []))
                ->values()
                ->map(function ($file, $index) use ($incomingBannerMeta) {
                    $storedPath = $this->storeUploadedFile($file, 'banners');
                    $meta = $incomingBannerMeta->get($index, []);

                    return [
                        'path' => $storedPath,
                        'focusX' => $meta['focusX'] ?? 50,
                        'focusY' => $meta['focusY'] ?? 50,
                        'zoom' => $meta['zoom'] ?? 1,
                    ];
                })
                ->values();

            $validated['profile_banners'] = $retainedBanners
                ->concat($uploadedBanners)
                ->values()
                ->all();
        }

        if (! empty($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'data' => $this->serializeUser($user->fresh()),
            'user' => $this->serializeUser($user->fresh()),
        ]);
    }

    private function serializeUser(User $user)
    {
        return [
            'id'              => $user->id,
            'username'        => $user->username,
            'email'           => $user->email,
            'avatar'          => $user->avatar,
            'profile_banners' => collect($user->profile_banners ?? [])
                ->map(fn ($item) => $this->normalizeBannerItem($item))
                ->filter()
                ->values()
                ->all(),
            'role'            => $user->role,
            'bio'             => $user->bio,
        ];
    }

    private function normalizeBannerItem($item): ?array
    {
        if (is_string($item)) {
            $decoded = json_decode($item, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $item = $decoded;
            } else {
                $item = ['path' => $item];
            }
        }

        if (! is_array($item)) {
            return null;
        }

        $path = $item['path'] ?? $item['url'] ?? null;

        if (! is_string($path) || $path === '') {
            return null;
        }

        return [
            'path' => $path,
            'focusX' => isset($item['focusX']) ? max(0, min(100, (float) $item['focusX'])) : 50,
            'focusY' => isset($item['focusY']) ? max(0, min(100, (float) $item['focusY'])) : 50,
            'zoom' => isset($item['zoom']) ? max(1, min(2.5, (float) $item['zoom'])) : 1,
        ];
    }

    private function storeUploadedFile($file, $folder)
    {
        $directory = public_path('uploads/'.$folder);

        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $filename = uniqid($folder.'_').'.'.$file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return '/uploads/'.$folder.'/'.$filename;
    }

    private function deleteLocalUpload($path)
    {
        if (! $path || strpos($path, '/uploads/') !== 0) {
            return;
        }

        $fullPath = public_path(ltrim($path, '/'));

        if (File::exists($fullPath)) {
            File::delete($fullPath);
        }
    }

    private function assertValidImage($file, string $field, int $maxKilobytes): void
    {
        if (! $file || ! $file->isValid()) {
            throw ValidationException::withMessages([
                $field => ['上传文件无效，请重新选择图片。'],
            ]);
        }

        if (($file->getSize() ?? 0) > ($maxKilobytes * 1024)) {
            throw ValidationException::withMessages([
                $field => [sprintf('图片大小不能超过 %d MB。', (int) ceil($maxKilobytes / 1024))],
            ]);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: '');
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

        if (! in_array($extension, $allowedExtensions, true)) {
            throw ValidationException::withMessages([
                $field => ['仅支持 jpg、jpeg、png、gif、webp、bmp 格式图片。'],
            ]);
        }

        if (! @getimagesize($file->getRealPath())) {
            throw ValidationException::withMessages([
                $field => ['上传文件不是可识别的图片。'],
            ]);
        }
    }
}
