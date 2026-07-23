<?php

namespace App\Repositories\Contracts;

use App\Models\OtpCode;

interface OtpRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): OtpCode;

    public function save(OtpCode $otp): void;

    /** Invalide (consomme) les codes encore actifs d'un numéro avant d'en émettre un nouveau. */
    public function invalidateActiveForPhone(string $phone): void;

    /** Dernier code non vérifié, non expiré et non consommé du numéro. */
    public function findLatestActiveByPhone(string $phone): ?OtpCode;

    /** Ticket d'inscription valide (numéro prouvé, non expiré, non consommé). */
    public function findValidRegistration(string $tokenHash): ?OtpCode;

    /** Nombre de demandes d'OTP pour ce numéro depuis une date (rate-limit). */
    public function countRequestsSince(string $phone, \DateTimeInterface $since): int;
}
