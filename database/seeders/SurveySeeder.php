<?php

namespace Database\Seeders;

use App\Models\InformasiKontak;
use App\Models\MasterAspek;
use App\Models\MasterSkala;
use Illuminate\Database\Seeder;

class SurveySeeder extends Seeder
{
    public function run(): void
    {
        $aspek = [
            'Kemudahan Layanan' => [
                'Kemudahan prosedur pengajuan proposal penelitian',
                'Kejelasan informasi persyaratan berkas',
            ],
            'Kecepatan Layanan' => [
                'Kecepatan proses verifikasi berkas',
                'Kecepatan respon petugas terhadap pertanyaan',
            ],
            'Kualitas Layanan' => [
                'Keramahan dan profesionalisme petugas',
                'Kepuasan terhadap layanan secara keseluruhan',
            ],
        ];

        $u = 0;
        foreach ($aspek as $nama => $pertanyaan) {
            $a = MasterAspek::firstOrCreate(['nama_aspek' => $nama], ['urutan' => ++$u * 10]);
            foreach ($pertanyaan as $i => $p) {
                $a->pertanyaan()->firstOrCreate(['pertanyaan' => $p], ['urutan' => ($i + 1) * 10]);
            }
        }

        $skala = ['Sangat Tidak Puas' => 1, 'Tidak Puas' => 2, 'Cukup' => 3, 'Puas' => 4, 'Sangat Puas' => 5];
        foreach ($skala as $nama => $nilai) {
            MasterSkala::firstOrCreate(['nama_skala' => $nama], ['nilai' => $nilai, 'urutan' => $nilai * 10]);
        }

        InformasiKontak::firstOrCreate([], [
            'email' => 'cru@rspi.test',
            'telepon' => '021-0000000',
            'pemilik_rekening' => 'RSPI',
            'nomor_rekening' => '000-000-0000',
            'nama_bank' => 'Bank Demo',
            'deskripsi_biaya' => 'Biaya administrasi penelitian sesuai ketentuan yang berlaku.',
        ]);
    }
}
