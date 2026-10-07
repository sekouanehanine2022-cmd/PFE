@extends('layouts.auth')

@section('title', 'Adresse e-mail verifiee - EFEL')

@section('content')
<div class="login-wrapper verification-confirmation-wrapper">
    <div class="login-card verification-card" id="verification-confirmation">
        <div class="verification-success-state" role="status">
            <div class="verification-success-icon">
                <i class="bi bi-check-lg"></i>
            </div>
            <h2>Adresse e-mail verifiee</h2>
            <p>Votre adresse a ete verifiee avec succes.</p>
            <a href="{{ route('dashboard') }}" class="btn-login verification-continue-link">
                <i class="bi bi-arrow-right"></i>
                Continuer vers l'application
            </a>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('js/verification-email.js') }}"></script>
@endsection
