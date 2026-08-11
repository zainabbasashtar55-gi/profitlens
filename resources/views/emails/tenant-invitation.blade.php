<x-mail::message>
# You've been invited to {{ $tenant }}

@isset($invitedBy)
{{ $invitedBy }} has invited you to join **{{ $tenant }}** as a **{{ $role }}**.
@else
You've been invited to join **{{ $tenant }}** as a **{{ $role }}**.
@endisset

<x-mail::button :url="$acceptUrl">
Accept invitation
</x-mail::button>

This invitation expires in 7 days.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
