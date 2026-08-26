@extends('layouts.app')

@section('title', 'Sign In - FBR Digital Invoicing')

@section('content')
<div class="row justify-content-center align-items-center min-vh-80 my-5">
    <div class="col-12 col-sm-10 col-md-8 col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="text-center mb-4">
                <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-3 mb-3" style="width:48px; height:48px; font-weight:800; font-size:1.2rem;">
                    FBR
                </div>
                <h4 class="fw-bold text-dark mb-1">FBR Invoicing Gateway</h4>
                <p class="text-muted small">Sign in to your shop admin account</p>
            </div>

            <form action="{{ route('login') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label fw-medium small">Email Address</label>
                    <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', 'owner@almadina.pk') }}" required autofocus>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label fw-medium small">Password</label>
                    <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" value="password123" required>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember" checked>
                    <label class="form-check-label small text-muted" for="remember">Remember me on this device</label>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2.5 fw-bold shadow-sm mb-3">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to Dashboard
                </button>

                <div class="text-center small text-muted mb-3">
                    Don't have an account? <a href="{{ route('register') }}" class="fw-semibold text-primary">Register new shop account</a>
                </div>
            </form>

            <div class="pt-3 border-top text-center bg-light rounded-3 p-3">
                <div class="text-uppercase text-muted" style="font-size:10px; font-weight:700;">Demo Login Credentials</div>
                <div class="small mt-1 font-monospace text-dark">owner@almadina.pk &bull; password123</div>
            </div>
        </div>
    </div>
</div>
@endsection
