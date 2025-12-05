export function formatCurrencyBRL(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
}

export function formatDate(date) {
    const d = new Date(date);

    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();

    return `${day}/${month}/${year}`;
}

export function formatStatus(value) {
    const label =
        value === "active"
            ? "Ativo"
            : value === "inactive"
                ? "Inativo"
                : value;

    const statusClass =
        value === "active"
            ? "badge bg-success"
            : value === "inactive"
                ? "badge bg-secondary"
                : "badge bg-warning text-dark";

    return `<span class="${statusClass}">${label}</span>`;
}
