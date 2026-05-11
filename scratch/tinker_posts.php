use App\Models\Post;
use Illuminate\Support\Str;

$post1 = [
    'title' => 'A Importância da Gestão Estratégica no Terceiro Setor',
    'excerpt' => 'Como a profissionalização da gestão pode garantir a agilidade e a sustentabilidade das entidades sociais.',
    'content' => '<h1>Agilidade e Gestão no Terceiro Setor</h1><p>No cenário atual, as entidades do terceiro setor enfrentam desafios crescentes de transparência e eficiência. Ter uma gestão profissionalizada não é mais um luxo, mas uma necessidade para garantir que os recursos cheguem onde são mais necessários.</p><h2>Por que a agilidade é crucial?</h2><p>Entidades que adotam sistemas integrados conseguem responder mais rápido a editais, gerir doadores com precisão e manter uma prestação de contas impecável. Isso gera confiança e atrai mais investimentos sociais.</p><blockquote>"A tecnologia é o grande equalizador para pequenas e médias ONGs que buscam impacto real."</blockquote><p>Ao centralizar processos em uma plataforma como a Vivensi, o gestor de projetos ganha tempo para focas na missão, enquanto a automação cuida da burocracia.</p>',
    'is_published' => true,
    'published_at' => now(),
    'meta_description' => 'Aprenda como a gestão estratégica traz agilidade para entidades do terceiro setor e melhora o impacto social.',
    'tags' => 'gestão, terceiro setor, agilidade, ongs',
];

$post2 = [
    'title' => 'Agilidade para Gestores de Projetos Sociais: O Guia Definitivo',
    'excerpt' => 'Descubra como ferramentas integradas reduzem gargalos operacionais e potencializam o impacto das suas ações.',
    'content' => '<h1>Gestão de Projetos com Agilidade</h1><p>Gestores de projetos sociais lidam com múltiplas frentes: voluntários, orçamentos, cronogramas e impacto direto na ponta. Sem ferramentas que centralizem esses dados, a perda de informações é inevitável.</p><h2>Benefícios da Gestão Centralizada</h2><ul><li><strong>Redução de Erros:</strong> Menos planilhas manuais significam menos falhas humanas.</li><li><strong>Transparência Total:</strong> Dados em tempo real para doadores e conselhos.</li><li><strong>Escalabilidade:</strong> Projetos bem geridos podem ser replicados e expandidos com facilidade.</li></ul><p>A agilidade no terceiro setor permite que uma entidade pequena opere com o rigor e a eficiência de uma grande organização corporativa, maximizando cada centavo investido no bem comum.</p>',
    'is_published' => true,
    'published_at' => now()->addMinutes(5),
    'meta_description' => 'Guia prático sobre agilidade para gestores de projetos sociais. Melhore sua operação com tecnologia.',
    'tags' => 'projetos sociais, agilidade, impacto, vivensi',
];

foreach ([$post1, $post2] as $data) {
    $data['slug'] = Str::slug($data['title']);
    Post::create($data);
}

echo "DONE";
