@extends('layouts.app')

@section('content')
<div style="background-color: #0f172a; min-height: 100vh; padding-bottom: 50px; margin: -1.5rem;">
    
    <!-- Hero Section -->
    <div style="background: linear-gradient(to right, #312e81, #4338ca); padding: 60px 20px; text-align: center; margin-bottom: 40px;">
        <h1 style="color: #fff; font-weight: 800; font-size: 2.5rem; letter-spacing: -1px; margin-bottom: 10px;">Vivensi Academy</h1>
        <p style="color: #e0e7ff; font-size: 1.1rem; max-width: 600px; margin: 0 auto;">Domine a gestão da sua organização com nossos cursos exclusivos.</p>
    </div>

    <!-- Courses Grid -->
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        
        @if($courses->count() > 0)
            <h3 style="color: #fff; font-weight: 700; margin-bottom: 25px; border-left: 4px solid #6366f1; padding-left: 15px;">Meus Cursos</h3>
            
            <div class="row">
                @foreach($courses as $course)
                <div class="col-md-4 mb-4">
                    <a href="{{ route('academy.show', $course->slug) }}" style="text-decoration: none;">
                        <div class="academy-card" style="background: #1e293b; border-radius: 16px; overflow: hidden; transition: transform 0.3s ease, box-shadow 0.3s ease; height: 100%; position: relative; border: 1px solid #334155;">
                            
                            <!-- Thumbnail -->
                            <div style="position: relative; padding-top: 56.25%; background: #0f172a;">
                                @if($course->thumbnail_url)
                                    <img src="{{ $course->thumbnail_url }}" alt="{{ $course->title }}" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">
                                @else
                                    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(45deg, #1e1b4b, #312e81);">
                                        <i class="fas fa-graduation-cap fa-3x" style="color: #6366f1; opacity: 0.5;"></i>
                                    </div>
                                @endif
                                
                                <!-- Play Overlay -->
                                <div class="play-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s ease;">
                                    <div style="width: 50px; height: 50px; background: #6366f1; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                                        <i class="fas fa-play" style="margin-left: 4px;"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- Content -->
                            <div style="padding: 20px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                                    <span style="background: rgba(99, 102, 241, 0.2); color: #818cf8; font-size: 0.75rem; font-weight: 700; padding: 4px 10px; border-radius: 20px;">CURSO</span>
                                    <span style="color: #94a3b8; font-size: 0.85rem;"><i class="far fa-clock me-1"></i> {{ $course->total_lessons }} aulas</span>
                                </div>
                                
                                <h4 style="color: #fff; font-weight: 700; font-size: 1.1rem; margin-bottom: 10px; line-height: 1.4;">{{ $course->title }}</h4>
                                <p style="color: #94a3b8; font-size: 0.9rem; margin-bottom: 20px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    {{ Str::limit(strip_tags($course->description), 80) }}
                                </p>

                                <!-- Progress Bar -->
                                <div style="margin-top: auto;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px; color: #cbd5e1; font-size: 0.8rem; font-weight: 600;">
                                        <span>Progresso</span>
                                        <span>{{ $course->progress }}%</span>
                                    </div>
                                    <div style="height: 6px; background: #334155; border-radius: 3px; overflow: hidden;">
                                        <div style="height: 100%; width: {{ $course->progress }}%; background: linear-gradient(90deg, #6366f1, #818cf8); border-radius: 3px;"></div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </a>
                </div>
                @endforeach
            </div>

            <style>
                .academy-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
                    border-color: #6366f1;
                }
                .academy-card:hover .play-overlay {
                    opacity: 1;
                }
            </style>
        @else
            <div style="text-align: center; padding: 100px 20px; color: #94a3b8;">
                <div style="margin-bottom: 20px; width: 80px; height: 80px; background: #1e293b; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="fas fa-graduation-cap fa-2x"></i>
                </div>
                <h3>Em breve...</h3>
                <p>Nossos cursos estão sendo preparados com carinho para você!</p>
            </div>
        @endif

    </div>
</div>
@endsection
