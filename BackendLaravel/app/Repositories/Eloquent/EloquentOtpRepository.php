<?php

namespace App\Repositories\Eloquent;

use App\Models\OtpCode;
use App\Repositories\Contracts\OtpRepositoryInterface;
use Illuminate\Support\Carbon;

class EloquentOtpRepository implements OtpRepositoryInterface
{
    public function create(array $data): OtpCode
    {
        return OtpCode::create($data);
    }

    public function save(OtpCode $otp): void
    {
        $otp->save();
    }

    public function invalidateActiveForPhone(string $phone): void
    {
        OtpCode::query()
            ->where('telephone', $phone)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => Carbon::now()]);
    }

    public function findLatestActiveByPhone(string $phone): ?OtpCode
    {
        return OtpCode::query()
            ->where('telephone', $phone)
            ->whereNull('consumed_at')
            ->whereNull('verified_at')
            ->where('expires_at', '>', Carbon::now())
            ->latest('id')
            ->first();
    }

    public function findValidRegistration(string $tokenHash): ?OtpCode
    {
        return OtpCode::query()
            ->where('registration_token_hash', $tokenHash)
            ->whereNotNull('verified_at')
            ->whereNull('consumed_at')
            ->where('registration_expires_at', '>', Carbon::now())
            ->latest('id')
            ->first();
    }

    public function countRequestsSince(string $phone, \DateTimeInterface $since): int
    {
        return OtpCode::query()
            ->where('telephone', $phone)
            ->where('created_at', '>=', $since)
            ->count();
    }
}
