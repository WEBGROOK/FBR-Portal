@extends('layouts.app')

@section('title', 'Register - FBR Digital Invoicing')

@section('content')
<div class="row justify-content-center align-items-center min-vh-80 my-4">
    <div class="col-12 col-sm-10 col-md-8 col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="text-center mb-4">
                <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-3 mb-3" style="width:48px; height:48px; font-weight:800; font-size:1.2rem;">
                    FBR
                </div>
                <h4 class="fw-bold text-dark mb-1">Create Shop Admin Account</h4>
                <p class="text-muted small">Register your POS ID and Seller NTN to submit invoices directly to FBR</p>
            </div>

            <form action="{{ route('register') }}" method="POST">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label for="name" class="form-label fw-medium small">Shop / Business Name</label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. Al-Madina Traders" required autofocus>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="email" class="form-label fw-medium small">Email Address</label>
                        <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="owner@shop.pk" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label for="seller_ntn" class="form-label fw-medium small">Seller NTN</label>
                        <input type="text" name="seller_ntn" id="seller_ntn" class="form-control @error('seller_ntn') is-invalid @enderror" value="{{ old('seller_ntn') }}" placeholder="1234567-8" required>
                        <div class="form-text" style="font-size:11px;">7 digits followed by check digit</div>
                        @error('seller_ntn')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="pos_id" class="form-label fw-medium small">FBR POS Registration ID</label>
                        <input type="text" name="pos_id" id="pos_id" class="form-control @error('pos_id') is-invalid @enderror" value="{{ old('pos_id') }}" placeholder="POS-100234" required>
                        <div class="form-text" style="font-size:11px;">Assigned by FBR POS portal</div>
                        @error('pos_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6">
                        <label for="password" class="form-label fw-medium small">Password</label>
                        <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="password_confirmation" class="form-label fw-medium small">Confirm Password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2.5 fw-bold shadow-sm mb-3">
                    <i class="bi bi-person-plus me-1"></i> Register Shop Account
                </button>

                <div class="text-center small text-muted">
                    Already registered? <a href="{{ route('login') }}" class="fw-semibold text-primary">Sign in here</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
