export default function FormField({
    autoComplete,
    errors,
    helpText,
    label,
    name,
    onChange,
    type = 'text',
    value,
}) {
    const hasError = Boolean(errors?.length);

    return (
        <div>
            <label className="label mb-1.5 normal-case tracking-normal" htmlFor={name}>
                {label}
            </label>
            <input
                autoComplete={autoComplete}
                className={`input-field rounded-lg px-3.5 py-2.5 text-sm ${hasError ? 'input-error' : ''}`}
                id={name}
                name={name}
                onChange={onChange}
                type={type}
                value={value}
            />
            {helpText && !hasError && <p className="mt-1.5 text-xs leading-5 text-gray-500">{helpText}</p>}
            <FieldError errors={errors} />
        </div>
    );
}

export function FieldError({ errors }) {
    if (!errors?.length) {
        return null;
    }

    return <p className="mt-2 text-sm font-medium text-danger">{errors[0]}</p>;
}
