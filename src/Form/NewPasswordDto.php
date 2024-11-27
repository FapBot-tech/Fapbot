<?php
declare(strict_types=1);


namespace App\Form;


class NewPasswordDto {

    public string $currentPassword;
    public string $newPassword;
    public string $newPasswordAgain;
}