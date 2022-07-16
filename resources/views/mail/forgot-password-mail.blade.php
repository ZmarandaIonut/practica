@component('mail::message')
# Recover Password

Your password is {{$password}}

Thanks,<br>
{{ config('app.name') }}
@endcomponent
