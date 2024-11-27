<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240308142734 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('UPDATE mute SET active = 0 WHERE active = 1');
        $this->addSql('UPDATE mute SET end_time = CURRENT_TIMESTAMP WHERE end_time > NOW()');

        $oldAndNewIdentifiers = [
            '66soQSiivJ2sivTgZ'=> 'atscdn45ibdt7cocrjkww96myw',
            'GENERAL' => '351g4oc31ibeudqowuaeqggwkr',
            '3S9WZ3ydb7nq8YST5' => 'bktpgcppbifxfn5bwyqw36jpee',
            'mGh6S3FEDXFjmtMP4' => '739sg7zpzpyh98m77436r5uepy',
            'EC8qQxXvJR4SYCzTd' => '6zou197cwbyg3p5uhz4qutia9a',
            'HkMgNJzwiLDitfBfw' => '3xwrazbw53gcjmucgbpay69ror',
            'Kfpu7KYLXqPBi3zre' => 'r8th6weryjg6xeerorrt6qth5h',
            'Nf3o2ApPTG2wCcfTw' => 'tyo49iza9tfrzy1xumor9cp69h',
            'm4zynmeTzNTjxf5C6' => 'u9zqzds85tys8x5c9yp8xjk6hw',
            'HDQBctDT5XHuHHASF' => 'y5insesmcfnwmrfa1en7xt4inr',
            'XfcniCmLSKEfuBJdw' => '89mxh4478br3prisz8qeuwkr7c',
            'oXaXyqWNA5RKiDjCR' => 'ykfrdhj6g3du5jwgrwgigqs9ea',
            'SdxzT85PK4wuupG6r' => '1bkhr6orafdi8btyacjuripfeo',
            'MasnCvBGpe9GvNoLy' => 'b84nxm7xui8f9caeha777a9zwh',
            'kpfoxwfHC2o47ukJ2' => '6idouxbdq7ns7b44it9smqnh1y',
            'wtytTpYBRbPs2Ggu9' => 'wqiz714f9igq7ffsxjh6zpgcdc',
            'N8SxsRc86eSrGveJm' => 'qfxfrf4kk7rau89xi71hnn4jho',
            'yEoeGRHkug7yfmRKz' => 'wds5myqnujrq384yqha7nbkyre',
            'MpdrxtCsTzgktyKFk' => 'djtr5nnxxpyrfjpmrq1ce9ac5y',
            'qhRf7BMJi6ZaMm7Hz' => 'r3iqaq8cijbs3qon5ro9i6rdty',
            'mkGkbpXfp6QFMD9Q7' => '768yct85sig6if1npc5thta3oa',
            '96CrZDBXtjKf9Ln4b' => 'dpkgzd4jmirnjds544mwebmx4c',
            '9wKsK6oKgKtWgjqdT' => 'etrak3cxqtnnffrhcofdyi7h9a',
            'FrHF7SHTFAG5pbP8P' => '59sruwgqstrwifj4fhmoqapowr',
            'gX2mT5XwEJHJJcPWM' => '1khtb5f77pgbzm66egspo945fw',
            'QBgLAprkuhJCJHAuG' => 'an3f86gwf7nktmwas5cf5wyjjh',
            'ndqzksMXfXhgj3dGm' => '33hhdcb5qff7tkgf5nt1mue1pw',
            '33vq5hzCH6YezoAAs' => 's3gsoyw4g7b7fmur6a3cf6maow',
            'AaNnLKDuJ4RLtKGNA' => 'bue8x84xjtb7brxonq3wnc4och',
            '7Xfn86EYaCC7g3Ebx' => 'w51bs9dtbjyt5nf8uh56dwx97a',
            'J6skvrx6ixTLYRiGh' => '53rokk8oxbygte943i3neqdbxw',
            'LmhnAtgRsHQChDXEe' => 'per61zjyyidwmc1ie6smkcxbzw',
            'MRPfh3LJki54miZxM' => 'de598g7jsjbhiy1goszpf1fzbe',
            'ENufp8gexzkoYZ9wX' => 'jtt4115hrjg1fjfbsur5w5d9ye',
            'hMsEJ5oD5QMATzavj' => 'iwzdmwod57fyjn1b58rzatiorr',
            'D2EF27QZq7adahbew' => '1jjpjurqepd3jgga6tenbem5uo',
            'mpGBwsfZkshjmFW44' => 'do365ps9ijgttedxenh8xp6fzr',
            '9MrQyQsJvAwJczcWg' => 'b8h1o47fcbgspdx6oyinbxgr7h',
            'tkcT9QsqHHZG52uAr' => 'ozzoo6ggqintdrwyzsi3gndjzr',
            'HF8iDBNWYg5L9knSY' => 'kok5ejko5jbzxdy9s8pmy1noce',
            'FnFtMh7H2dPFdQPXN' => 'drqidt646tfhtyochysox7o4zr',
            'essfHihwGd4qgrQFe' => 'ft15oeaaejgz9pzjfz58qyernc',
            'x2RXYicc5ZaJMhmDw' => 'rbgfatjrubnedfgx7st59646xc',
            '8RnXSa9TaDeXpRD2t' => 'dabdu1hzcpbopytf4omy8r4hoo',
            'vkbC43cRgcQLWWfAk' => 'uifdap74jbrffcr56forcic43c',
            'tztuk65DAiZ3cqDAn' => 'oux165kkbp8ftryusnwskaytor',
        ];
        foreach ($oldAndNewIdentifiers as $old => $new) {
            $this->addSql('UPDATE channel SET identifier = ? WHERE identifier = ?', [$new, $old]);
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
