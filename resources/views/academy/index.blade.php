@extends('layouts.academy')

@section('content')
<div style="background-color: #0f172a; min-height: 100vh; padding-bottom: 50px; margin: -1.5rem;">
    
    
    <!-- Hero Section with Featured Course -->
    <div style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%); padding: 80px 20px; margin-bottom: 50px; position: relative; overflow: hidden;">
        <!-- Decorative Elements -->
        <div style="position: absolute; top: -50px; right: -50px; width: 300px; height: 300px; background: rgba(99, 102, 241, 0.1); border-radius: 50%; filter: blur(60px);"></div>
        <div style="position: absolute; bottom: -100px; left: -100px; width: 400px; height: 400px; background: rgba(139, 92, 246, 0.1); border-radius: 50%; filter: blur(80px);"></div>
        
        <div class="container" style="max-width: 1200px; margin: 0 auto; position: relative; z-index: 1;">
            <div class="row align-items-center">
                <!-- Left: Text Content -->
                <div class="col-md-6 mb-4 mb-md-0">
                    <div style="display: inline-block; background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); padding: 8px 16px; border-radius: 30px; margin-bottom: 20px; border: 1px solid rgba(255, 255, 255, 0.2);">
                        <span style="color: #fbbf24; font-weight: 700; font-size: 0.85rem; letter-spacing: 1px;">✨ NOVO NA PLATAFORMA</span>
                    </div>
                    
                    <h1 style="color: #fff; font-weight: 900; font-size: 3rem; letter-spacing: -2px; margin-bottom: 20px; line-height: 1.1;">
                        Vivensi<br>
                        <span style="background: linear-gradient(90deg, #818cf8, #c084fc); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Academy</span>
                    </h1>
                    
                    <p style="color: #e0e7ff; font-size: 1.2rem; line-height: 1.6; margin-bottom: 30px; max-width: 500px;">
                        Transforme sua organização com cursos práticos e certificados reconhecidos.
                    </p>
                    
                    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <a href="#cursos" style="background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; padding: 15px 35px; border-radius: 12px; text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 10px 30px rgba(99, 102, 241, 0.4); transition: transform 0.2s;">
                            <i class="fas fa-play-circle"></i> Começar Agora
                        </a>
                        <a href="#sobre" style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); color: white; padding: 15px 35px; border-radius: 12px; text-decoration: none; font-weight: 700; border: 1px solid rgba(255, 255, 255, 0.2); transition: all 0.2s;">
                            <i class="fas fa-info-circle"></i> Saiba Mais
                        </a>
                    </div>
                </div>
                
                <!-- Right: Featured Course Card -->
                <div class="col-md-6">
                    @if($courses->count() > 0)
                        @php $featured = $courses->first(); @endphp
                        <div style="background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(20px); border-radius: 24px; padding: 25px; border: 1px solid rgba(255, 255, 255, 0.1); box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);">
                            <div style="position: relative; border-radius: 16px; overflow: hidden; margin-bottom: 20px;">
                                <img src="{{ $featured->thumbnail_url }}" alt="{{ $featured->title }}" style="width: 100%; height: 250px; object-fit: cover;">
                                <div style="position: absolute; top: 15px; left: 15px; background: rgba(99, 102, 241, 0.9); backdrop-filter: blur(10px); padding: 8px 16px; border-radius: 20px; font-weight: 700; color: white; font-size: 0.8rem;">
                                    <i class="fas fa-star"></i> EM DESTAQUE
                                </div>
                            </div>
                            
                            <h3 style="color: #fff; font-weight: 700; font-size: 1.4rem; margin-bottom: 10px;">{{ $featured->title }}</h3>
                            <p style="color: #cbd5e1; font-size: 0.95rem; margin-bottom: 20px;">{{ Str::limit(strip_tags($featured->description), 100) }}</p>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div style="display: flex; gap: 20px; color: #94a3b8; font-size: 0.85rem;">
                                    <span><i class="far fa-clock"></i> {{ $featured->total_lessons }} aulas</span>
                                    <span><i class="fas fa-signal"></i> {{ $featured->progress }}%</span>
                                </div>
                                <a href="{{ route('academy.show', $featured->slug) }}" style="background: #6366f1; color: white; padding: 10px 20px; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 0.9rem;">
                                    Continuar <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Courses Grid -->
    <div id="cursos" class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        
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
