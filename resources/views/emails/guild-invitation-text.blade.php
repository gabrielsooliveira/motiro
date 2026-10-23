Convite para Guild — Motirõ
===========================

Olá,

{{ $inviter ? $inviter->name : 'Um participante' }} convidou você para ingressar na guild permanente {{ $guild->name }} na plataforma Motirõ.

Guild: {{ $guild->name }}
Descrição: {{ $guild->description ?: 'Guild independente de hackathons para colaboração e desenvolvimento de projetos.' }}

Para visualizar os detalhes da guild e responder ao convite (aceitar ou recusar), acesse o link abaixo:
{{ $inviteUrl }}

@if($invitation->expires_at)
Este convite é válido até {{ $invitation->expires_at->format('d/m/Y \à\s H:i') }}.
@endif

Caso você não tenha solicitado ou não reconheça este convite, basta desconsiderar esta mensagem.

Motirõ — Plataforma de Inovação e Hackathons.
