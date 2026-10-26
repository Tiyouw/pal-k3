@props(['roles' => []])

@if (config('demo.enabled'))
    <aside class="k3-demo" aria-label="Akun demonstrasi">
        <strong>Akun demo <small>Username / kata sandi</small></strong>
        @foreach (config('demo.accounts') as $username => $account)
            @if (in_array($account['role'], $roles, true))
                <p><span>{{ $username }}</span><code>{{ $account['password'] }}</code></p>
            @endif
        @endforeach
    </aside>
@endif
