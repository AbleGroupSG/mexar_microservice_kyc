<?php

namespace App\Services\KYB;

use App\Services\KYC\Regtank\RegtankAuth;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RegtankDowJoneKYBService extends AbstractKYBService
{

    const ASSIGNEE = 'evone@ablegroup.id';

    /**
     * @throws ConnectionException
     * @throws \Throwable
     */
    public function checkStatus(string $identity): mixed
    {
        $accessToken = RegtankAuth::getToken();
        $url = config('e-form.regtank.specific_server_url');
        $params = [
            'requestId' => $identity,
        ];

        return Http::withToken($accessToken)
            ->get("$url/v3/djkyb/query" . '?' . http_build_query($params))
            ->json();
    }

    /**
     * @throws ConnectionException
     * @throws \Throwable
     */
    public function createProfile(array $data): mixed
    {
        $accessToken = RegtankAuth::getToken();
        $url = config('regtank.specific_server_url');
        return Http::withToken($accessToken)
            ->post("$url/v3/djkyb/input", $data)
            ->json();
    }

    public function updateStatuses(Collection $entities): void
    {
        foreach ($entities as $entity) {
            try {
                $response = $this->checkStatus($entity->companyKyb->request_id);

                if($error = $response['error'] ?? null) {
                    Log::error('Error for entity ID ' . $entity->id . ': ' . $error . ' Status: ' .  $response['status']);
                    continue;
                }

                $data = $response[0];
                $score = $data['corporateRiskScore'] ? $data['corporateRiskScore']['score'] : null;
                $entity->companyKyb()->update([
                    'status' => $data['status'],
                    'last_checked_at' => now(),
                    'risk_score' => $score,
                ]);
                sleep(1);
            } catch (ConnectionException $e) {
                Log::error('ConnectionException for entity ID ' . $entity->id . ': ' . $e->getMessage());
            } catch (\Throwable $e) {
                Log::error('Exception for entity ID ' . $entity->id . ': ' . $e->getMessage());
            }
        }
    }

    public function prepareData(Company $company): array
    {
        $dateOfIncorporation = $company->singaporeCompanyIncorporationForm->preferred_incorporation_date
            ? Carbon::parse($company->singaporeCompanyIncorporationForm->preferred_incorporation_date)
                ->format('Y-m-d')
            : null;
        $addressLine = $this->getCompanyInfo($company, 'address_line_1')
            . ' ' . $this->getCompanyInfo($company, 'address_line_2')
            . ' ' . $this->getCompanyInfo($company, 'address_line_3');
        return [
            'referenceId' => $company->id,
            'businessName' => $company->company_name, // required
            'businessIdNumber' => $company->singaporeCompanyComplianceForm->business_id_number, // required
            'address1' => $addressLine, // not required
            'email' => $this->getCompanyInfo($company, 'email'), // not required
            'phone' => $this->getCompanyInfo($company, 'contact_number'), // not required
            'website' => $this->getCompanyInfo($company, 'website'), // not required
//            'countryOfHeadQuarter' => 'string', // not required
//            'countryOfIncorporation' => 'string', // not required
//            'operatingCountry' => 'string', // not required
            'dateOfIncorporation' => $dateOfIncorporation, // not required
            'assignee' => self::ASSIGNEE,
        ];
    }

    private function getCompanyInfo(Company $company, string $param): mixed
    {
        return $company->singaporeCompanyComplianceForm->{$param} ??
            $company->singaporeCompanyIncorporationForm->{$param};
    }
}
