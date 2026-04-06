# ROADMAP: Aprovação Oficial Meta (Embedded Signup)

Para que o Vivensi tenha o botão azul "Conectar com Facebook" (que abre a janelinha oficial em vez de pedir IDs manuais), você precisa passar pelo processo de revisão da Meta.

## 1. Pré-Requisitos de Negócio (Business Manager)
- **CNPJ Ativo:** Sua empresa (Vivensi) precisa estar ativa.
- **Verificação de Empresa:** Vá no Gerenciador de Negócios (Business Settings > Security Center) e clique em "Start Verification". Você precisará enviar documentos da empresa.
- **Domínio Verificado:** O domínio `vivensi.app.br` precisa estar verificado no Business Manager.

## 2. Requisitos Técnicos do App (developers.facebook.com)
- **App Type:** O App precisa ser do tipo **Business**.
- **Página de Privacidade:** Você **DEVE** ter um link público (ex: `vivensi.app.br/privacidade`) explicando como os dados do WhatsApp são tratados. Sem isso, a Meta rejeita de imediato.
- **Login com Facebook:** Adicione o produto "Facebook Login".
- **WhatsApp API:** Adicione o produto "WhatsApp".

## 3. Revisão de Permissões (App Review)
Para o fluxo automático (OBO - On-Behalf-Of), você precisará solicitar estas permissões na Meta:
- `whatsapp_business_management`
- `whatsapp_business_messaging`

## 4. O Fluxo de Cadastro Incorporado (Embedded Signup)
Uma vez aprovado, o código do botão muda para usar o SDK da Meta:
```javascript
FB.login(function(response) {
   // Recebemos o Access Token do cliente e o ID da conta dele automaticamente.
}, {
   scope: 'whatsapp_business_management,whatsapp_business_messaging',
   extras: {
     feature: 'whatsapp_embedded_signup'
   }
});
```

---

### **Recomendação Imediata:**
Enquanto você não tem a aprovação (que pode levar de 3 a 10 dias), o **Playbook Manual** (que vou inserir agora na interface) é o caminho para você não parar de vender.
