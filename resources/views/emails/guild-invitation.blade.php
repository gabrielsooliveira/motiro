<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Convite para Guilda</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        .container {
            max-width: 580px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            border: 1px solid #e2e8f0;
        }
        .header {
            background-color: #0f172a;
            padding: 28px 32px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            font-size: 24px;
            margin: 0;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .header .badge {
            display: inline-block;
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 9999px;
            margin-top: 6px;
        }
        .content {
            padding: 32px;
        }
        .greeting {
            font-size: 16px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 12px;
        }
        .guild-card {
            background-color: #f1f5f9;
            border-radius: 8px;
            padding: 20px;
            margin: 24px 0;
            border-left: 4px solid #3b82f6;
        }
        .guild-name {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 8px 0;
        }
        .guild-desc {
            font-size: 14px;
            color: #475569;
            margin: 0;
        }
        .btn-wrapper {
            text-align: center;
            margin: 32px 0 24px 0;
        }
        .btn {
            display: inline-block;
            background-color: #2563eb;
            color: #ffffff !important;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
        }
        .btn:hover {
            background-color: #1d4ed8;
        }
        .meta-info {
            font-size: 13px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            padding-top: 16px;
            margin-top: 24px;
        }
        .footer {
            background-color: #f8fafc;
            padding: 20px 32px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Motirõ</h1>
            <div class="badge">Convite para Guilda</div>
        </div>
        <div class="content">
            <p class="greeting">Olá,</p>
            <p>
                <strong>{{ $inviter ? $inviter->name : 'Um participante' }}</strong> convidou você para ingressar na guilda permanente <strong>{{ $guild->name }}</strong> na plataforma Motirõ.
            </p>

            <div class="guild-card">
                <h3 class="guild-name">{{ $guild->name }}</h3>
                <p class="guild-desc">
                    {{ $guild->description ?: 'Guilda independente de hackathons para colaboração e desenvolvimento de projetos.' }}
                </p>
            </div>

            <p style="font-size: 14px; color: #475569;">
                Como membro desta guilda, você poderá colaborar com o time, participar de futuros eventos e planejar projetos coletivos.
            </p>

            <div class="btn-wrapper">
                <a href="{{ $inviteUrl }}" class="btn">Visualizar e Responder ao Convite</a>
            </div>

            <div class="meta-info">
                @if($invitation->expires_at)
                    <p style="margin: 0 0 8px 0;">
                        ⏱ <strong>Validade:</strong> Este convite expira em <strong>{{ $invitation->expires_at->format('d/m/Y \à\s H:i') }}</strong>.
                    </p>
                @endif
                <p style="margin: 0; word-break: break-all;">
                    Caso o botão acima não funcione, copie e cole o seguinte link em seu navegador:<br>
                    <a href="{{ $inviteUrl }}" style="color: #2563eb;">{{ $inviteUrl }}</a>
                </p>
            </div>
        </div>
        <div class="footer">
            <p style="margin: 0 0 4px 0;">&copy; {{ date('Y') }} Motirõ. Plataforma de Inovação e Hackathons.</p>
            <p style="margin: 0;">Se você não esperava este convite, pode ignorar este e-mail com segurança.</p>
        </div>
    </div>
</body>
</html>
