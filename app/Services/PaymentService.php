<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class PaymentService
{
    protected $apiKey;
    protected $merchantId;
    protected $baseUrl;
    protected $webhookSecret;

    public function __construct()
    {
        $this->apiKey = config('services.payment.api_key');
        $this->merchantId = config('services.payment.merchant_id');
        $this->baseUrl = config('services.payment.base_url', 'https://api.payment-provider.com');
        $this->webhookSecret = config('services.payment.webhook_secret');
    }

    /**
     * Create payment transaction
     */
    public function createPayment(array $data)
    {
        try {
            $payload = [
                'merchant_id' => $this->merchantId,
                'order_id' => $data['order_id'],
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'IDR',
                'description' => $data['description'],
                'customer' => [
                    'name' => $data['customer_name'],
                    'email' => $data['customer_email'],
                    'phone' => $data['customer_phone'] ?? null,
                ],
                'items' => $data['items'] ?? [],
                'return_url' => $data['return_url'],
                'notify_url' => $data['notify_url'],
                'expiry_minutes' => $data['expiry_minutes'] ?? 60,
                'payment_methods' => $data['payment_methods'] ?? ['credit_card', 'bank_transfer', 'ewallet'],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/payments', $payload);

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Payment creation failed', [
                    'response' => $response->body(),
                    'payload' => $payload
                ]);
                throw new \Exception('Payment creation failed: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Payment service error', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus($paymentId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/payments/' . $paymentId);

            if ($response->successful()) {
                return $response->json();
            } else {
                throw new \Exception('Failed to get payment status');
            }
        } catch (\Exception $e) {
            Log::error('Payment status check failed', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Process refund
     */
    public function processRefund($paymentId, $amount, $reason = '')
    {
        try {
            $payload = [
                'amount' => $amount,
                'reason' => $reason,
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/payments/' . $paymentId . '/refund', $payload);

            if ($response->successful()) {
                return $response->json();
            } else {
                throw new \Exception('Refund failed: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Refund failed', [
                'payment_id' => $paymentId,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature($payload, $signature)
    {
        $expectedSignature = hash_hmac('sha256', $payload, $this->webhookSecret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Create recurring payment
     */
    public function createRecurringPayment(array $data)
    {
        try {
            $payload = [
                'merchant_id' => $this->merchantId,
                'subscription_id' => $data['subscription_id'],
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'IDR',
                'description' => $data['description'],
                'customer' => [
                    'name' => $data['customer_name'],
                    'email' => $data['customer_email'],
                    'phone' => $data['customer_phone'] ?? null,
                ],
                'schedule' => [
                    'interval' => $data['interval'], // 'daily', 'weekly', 'monthly'
                    'day_of_month' => $data['day_of_month'] ?? null,
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'] ?? null,
                ],
                'payment_methods' => $data['payment_methods'] ?? ['credit_card', 'bank_transfer'],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/recurring-payments', $payload);

            if ($response->successful()) {
                return $response->json();
            } else {
                throw new \Exception('Recurring payment creation failed: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Recurring payment creation failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Cancel recurring payment
     */
    public function cancelRecurringPayment($subscriptionId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->delete($this->baseUrl . '/recurring-payments/' . $subscriptionId);

            if ($response->successful()) {
                return $response->json();
            } else {
                throw new \Exception('Failed to cancel recurring payment');
            }
        } catch (\Exception $e) {
            Log::error('Recurring payment cancellation failed', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get payment methods
     */
    public function getPaymentMethods()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/payment-methods');

            if ($response->successful()) {
                return $response->json();
            } else {
                throw new \Exception('Failed to get payment methods');
            }
        } catch (\Exception $e) {
            Log::error('Failed to get payment methods', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Calculate payment fees
     */
    public function calculateFees($amount, $paymentMethod)
    {
        $fees = [
            'credit_card' => [
                'fixed' => 2500, // 2,500 IDR
                'percentage' => 2.9, // 2.9%
            ],
            'bank_transfer' => [
                'fixed' => 4000, // 4,000 IDR
                'percentage' => 0,
            ],
            'ewallet' => [
                'fixed' => 1500, // 1,500 IDR
                'percentage' => 1.5, // 1.5%
            ],
        ];

        $methodFees = $fees[$paymentMethod] ?? $fees['bank_transfer'];
        
        $fixedFee = $methodFees['fixed'];
        $percentageFee = ($amount * $methodFees['percentage']) / 100;
        
        return [
            'fixed_fee' => $fixedFee,
            'percentage_fee' => $percentageFee,
            'total_fee' => $fixedFee + $percentageFee,
            'net_amount' => $amount - ($fixedFee + $percentageFee),
        ];
    }

    /**
     * Create payment link
     */
    public function createPaymentLink(array $data)
    {
        try {
            $payload = [
                'merchant_id' => $this->merchantId,
                'title' => $data['title'],
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'IDR',
                'description' => $data['description'],
                'customer' => [
                    'name' => $data['customer_name'] ?? null,
                    'email' => $data['customer_email'] ?? null,
                    'phone' => $data['customer_phone'] ?? null,
                ],
                'payment_methods' => $data['payment_methods'] ?? ['credit_card', 'bank_transfer', 'ewallet'],
                'expiry_minutes' => $data['expiry_minutes'] ?? 1440, // 24 hours
                'redirect_url' => $data['redirect_url'] ?? null,
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/payment-links', $payload);

            if ($response->successful()) {
                return $response->json();
            } else {
                throw new \Exception('Payment link creation failed: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Payment link creation failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Get transaction history
     */
    public function getTransactionHistory(array $filters = [])
    {
        try {
            $params = [
                'merchant_id' => $this->merchantId,
                'limit' => $filters['limit'] ?? 50,
                'offset' => $filters['offset'] ?? 0,
            ];

            if (isset($filters['start_date'])) {
                $params['start_date'] = $filters['start_date'];
            }
            if (isset($filters['end_date'])) {
                $params['end_date'] = $filters['end_date'];
            }
            if (isset($filters['status'])) {
                $params['status'] = $filters['status'];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/transactions', $params);

            if ($response->successful()) {
                return $response->json();
            } else {
                throw new \Exception('Failed to get transaction history');
            }
        } catch (\Exception $e) {
            Log::error('Transaction history fetch failed', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            throw $e;
        }
    }

    /**
     * Validate payment data
     */
    public function validatePaymentData(array $data)
    {
        $rules = [
            'order_id' => 'required|string|max:100',
            'amount' => 'required|numeric|min:1000',
            'currency' => 'required|string|size:3',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'description' => 'required|string|max:500',
            'return_url' => 'required|url',
            'notify_url' => 'required|url',
        ];

        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $rulesArray = explode('|', $rule);
            
            foreach ($rulesArray as $singleRule) {
                if ($singleRule === 'required' && empty($data[$field])) {
                    $errors[$field] = "Field {$field} is required";
                    break;
                }
                
                if (!empty($data[$field])) {
                    if ($singleRule === 'string' && !is_string($data[$field])) {
                        $errors[$field] = "Field {$field} must be a string";
                    }
                    
                    if (str_starts_with($singleRule, 'max:')) {
                        $maxLength = (int) substr($singleRule, 4);
                        if (strlen($data[$field]) > $maxLength) {
                            $errors[$field] = "Field {$field} must not exceed {$maxLength} characters";
                        }
                    }
                    
                    if ($singleRule === 'email' && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                        $errors[$field] = "Field {$field} must be a valid email";
                    }
                    
                    if ($singleRule === 'url' && !filter_var($data[$field], FILTER_VALIDATE_URL)) {
                        $errors[$field] = "Field {$field} must be a valid URL";
                    }
                    
                    if ($singleRule === 'numeric' && !is_numeric($data[$field])) {
                        $errors[$field] = "Field {$field} must be numeric";
                    }
                    
                    if ($singleRule === 'min:1000' && is_numeric($data[$field]) && $data[$field] < 1000) {
                        $errors[$field] = "Field {$field} must be at least 1000";
                    }
                }
            }
        }

        return $errors;
    }
}
