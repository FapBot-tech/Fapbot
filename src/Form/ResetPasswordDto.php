<?php
declare(strict_types=1);


namespace App\Form;


class ResetPasswordDto {

    public string $newPassword;
    public string $newPasswordAgain;
}