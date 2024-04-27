<?php

namespace App\Http\Controllers;

use Exception;

abstract class Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);
        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $tokenResult = $request->user()->createToken('authToken', ['*'], now()->addDays(15))->plainTextToken;
        return response()->json(['access_token' => $tokenResult]);
    }

    public function logout()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Not Authorized'], 401);
        }
        $user->currentAccessToken()->delete();

        return response()->json(['message' => 'user logged out successfully.']);
    }

    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->text('name');
            $table->text('email')->nullable()->default(null);
            $table->longText('website')->nullable()->default(null);
        });
    }

    public function down()
    {
        Schema::dropIfExits('companies');
    }

    public function up1()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->text('first_name');
            $table->text('last_name');
            $table->unsignedBigInteger('company_id');
            $table->text('email')->nullable()->default(null);
            $table->text('phone')->nullable()->default(null);

            $table->foreign('company_id')->refrence('companies')->on('id')->onDelete('RESTRICT');
        });
    }

    public function down1()
    {
        Schema::dropIfExits('employees');
    }

    public function definition(): array
    {
        return [
            'name' => fake()->companyTitle(),
            'email' => fake()->unique()->safeEmail(),
            'website' => fake()->url(),
        ];
    }

    public function definition1(): array
    {
        return [
            'firstName' => fake()->firstName(),
            'lastName' => fake()->lastName(),
            'company_id' => Company::inRandomOrder()->first()->id,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber()
        ];
    }

    public function index()
    {
        $perPage = 10;
        $companies = Company::paginate($perPage);
        $companyResource = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'website' => $this->website,
        ];
        return CompanyResource::collection($companies);
    }

    public function show(Company $company)
    {
        return new CompanyResource($company);
    }

    public function store(StoreCompanyRequest $request)
    {
        $validation = [
            'name' => 'required|string',
            'email' => 'nullable|email',
            'website' => 'nullable|url'
        ];
        try {
            $company = Company::create([
                "name" => $request->input("name"),
                "email" => $request->input("email"),
                "website" => $request->input("website")
            ]);

            return new CompanyRecource($company);
        } catch (Exception $e) {
            Log::info("Failed to create company. " . $e->getMessage());
            return response()->json(['message' => 'Failed to create company. Please try again later'], 500);
        }
    }

    public function destroy(Company $company)
    {
        try {
            if ($company->delete()) {
                return response()->json(['data' => 'Company deleted successfully']);
            }

            return response()->json(['message' => 'Failed to delete company.']);
        } catch (Exception $e) {
            Log::info("Failed to delete company. " . $e->getMessage());
            return response()->json(['message' => 'Failed to delete company. Please try again later'], 500);
        }
    }

    public function index1()
    {
        $perPage = 10;
        $employees = Employee::paginate($perPage);
        $employeeResource = [
            'id' => $this->id,
            'firstName' => $this->first_name,
            'lastName' => $this->last_name,
            'company' => new CompanyResource($this->company),
            'email' => $this->email,
            'phone' => $this->phone
        ];
        return EmployeeResource::collection($employees);
    }

    public function show1(Employee $employee)
    {
        return new EmployeeResource($employee);
    }

    public function store1(StoreEmployeeRequest $request)
    {
        $validation = [
            'firstName' => 'required|string',
            'lastName' => 'required|string',
            'companyId' => 'required|exist:companies,id',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
        ];
        try {
            $employee = Employee::create([
                "first_name" => $request->input('firstName'),
                "last_name" => $request->input('lastName'),
                "company_id" => $request->input('companyId'),
                "email" => $request->input('email'),
                "phone" => $request->input('phone'),
            ]);

            return new EmployeeResource($employee);
        } catch (Exception $e) {
            Log::info("Failed to create employee. " . $e->getMessage());
            return response()->json(['message' => 'Failed to create employee. Please try again later'], 500);
        }
    }

    public function update1(UpdateEmployeeRequest $request, Employee $employee)
    {
        $validation = [
            'firstName' => 'nullable|string',
            'lastName' => 'nullable|string',
            'companyId' => 'nullable|exist:companies,id',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
        ];


        try {
            $employee->update([
                "first_name" => $request->input('firstName') ?: $employee->fisrt_name,
                "last_name" => $request->input('lastName') ?: $employee->last_name,
                "company_id" => $request->input('companyId') ?: $employee->company_id,
                "email" => $request->input('email'),
                "phone" => $request->input('phone'),
            ]);

            return new EmployeeRecource($employee);
        } catch (Exception $e) {
            Log::info("Failed to update employee. " . $e->getMessage());
            return response()->json(['message' => 'Failed to update employee. Please try again later'], 500);
        }
    }

    public function update(UpdateCompanyRequest $request, Company $company)
    {
        $validation = [
            'name' => 'nullable|string',
            'email' => 'nullable|email',
            'website' => 'nullable|url'
        ];


        try {
            $company->update([
                'name' => $request->input('name') ?: $company->name,
                'email' => $request->input('email'),
                'website' => $request->input('website')
            ]);

            return new CompanyRecource($company);
        } catch (Exception $e) {
            Log::info("Failed to update company. " . $e->getMessage());
            return response()->json(['message' => 'Failed to update company. Please try again later'], 500);
        }
    }

    public function destroy1(Employee $employee)
    {
        try {
            if ($employee->delete()) {
                return response()->json(['data' => 'Employee deleted successfully']);
            }

            return response()->json(['message' => 'Failed to delete employee.'], 500);
        } catch (Exception $e) {
            Log::info("Failed to delete employee. " . $e->getMessage());
            return response()->json(['message' => 'Failed to delete employee. Please try again later'], 500);
        }
    }
}
