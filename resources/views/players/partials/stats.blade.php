{{-- Statistiques calculées sur les dernières parties (voir PlayerStatsService) --}}
@if ($stats === null)
    <p class="muted">Pas encore assez de parties pour calculer des statistiques.</p>
@else
    @php($summary = $stats['summary'])

    <p class="muted stats-scope">Sur les {{ $summary['games'] }} dernières parties (hors remakes)</p>

    {{-- Résumé --}}
    <div class="stats-summary">
        <div class="stat">
            <p class="stat-value {{ $summary['winrate'] >= 50 ? 'positive' : 'negative' }}">{{ $summary['winrate'] }} %</p>
            <p class="stat-label">{{ $summary['wins'] }}V {{ $summary['losses'] }}D</p>
        </div>

        <div class="stat">
            <p class="stat-value">{{ $summary['kda'] }}</p>
            <p class="stat-label">
                KDA &middot; {{ $summary['avg_kills'] }} / {{ $summary['avg_deaths'] }} / {{ $summary['avg_assists'] }}
            </p>
        </div>

        <div class="stat">
            <p class="stat-value">{{ $summary['cs_per_minute'] }}</p>
            <p class="stat-label">CS / minute</p>
        </div>

        <div class="stat">
            <p class="stat-value">{{ number_format($summary['avg_damage'], 0, ',', ' ') }}</p>
            <p class="stat-label">Dégâts moyens</p>
        </div>

        <div class="stat">
            <p class="stat-value">{{ $summary['avg_vision'] }}</p>
            <p class="stat-label">Score de vision</p>
        </div>
    </div>

    <div class="stats-details">
        {{-- Champions les plus joués --}}
        <div>
            <h3>Champions les plus joués</h3>

            @foreach ($stats['champions'] as $champion)
                <div class="champion-stat">
                    <img
                        src="{{ $ddragon->championImageUrl($champion['name']) }}"
                        alt="{{ $champion['name'] }}"
                        class="champion-icon"
                        width="36"
                        height="36"
                        loading="lazy"
                    >
                    <span class="champion-stat-name">{{ $champion['name'] }}</span>
                    <span class="muted">{{ $champion['games'] }} {{ $champion['games'] > 1 ? 'parties' : 'partie' }}</span>
                    <span class="{{ $champion['winrate'] >= 50 ? 'positive' : 'negative' }}">{{ $champion['winrate'] }} %</span>
                    <span class="muted">{{ $champion['kda'] }} KDA</span>
                </div>
            @endforeach
        </div>

        {{-- Rôles --}}
        <div>
            <h3>Rôles</h3>

            @forelse ($stats['roles'] as $role)
                <div class="role-stat">
                    <span class="role-stat-name">{{ $role['name'] }}</span>
                    <progress max="100" value="{{ $role['percent'] }}">{{ $role['percent'] }} %</progress>
                    <span class="muted">{{ $role['percent'] }} %</span>
                </div>
            @empty
                <p class="muted">Aucune partie avec un rôle défini.</p>
            @endforelse
        </div>
    </div>
@endif
