import React, { useEffect, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';

const money = (value) => new Intl.NumberFormat('ru-RU').format(value) + ' ₽';

function MiniCart({ initialCart, cartUrl, checkoutUrl, icons }) {
    const [cart, setCart] = useState(initialCart);
    const [open, setOpen] = useState(false);
    const [busy, setBusy] = useState(false);
    const [bump, setBump] = useState(false);
    const root = useRef(null);

    useEffect(() => {
        const onChanged = (event, nextCart, source) => {
            if (!nextCart) return;
            setCart(nextCart);
            if (source !== 'react') {
                setBump(true);
                setTimeout(() => setBump(false), 400);
            }
        };
        const onOutside = (event) => {
            if (root.current && !root.current.contains(event.target)) setOpen(false);
        };

        $(document).on('cart:changed', onChanged);
        document.addEventListener('click', onOutside);
        return () => {
            $(document).off('cart:changed', onChanged);
            document.removeEventListener('click', onOutside);
        };
    }, []);

    const setQuantity = (id, quantity) => {
        setBusy(true);
        // тот же обработчик, что и у страницы корзины: если она открыта, #cart-page обновится сам
        $.request('shopCart::onSetQty', {
            data: { id, quantity },
            success: function (data) {
                this.success(data);
                $(document).trigger('cart:changed', [data.cart, 'react']);
            },
            complete: () => setBusy(false),
        });
    };

    return (
        <div ref={root}>
            <button
                type="button"
                className={'mini-cart-toggle' + (bump ? ' bump' : '')}
                aria-expanded={open}
                onClick={() => setOpen(!open)}
            >
                <svg className="icon" aria-hidden="true"><use href={icons + '#shopping-bag'} /></svg>
                <span className="mini-cart-label">Корзина</span>
                <b>{cart.count}</b>
            </button>

            {open && (
                <div className="mini-cart-panel" aria-busy={busy}>
                    {cart.lines.length === 0 ? (
                        <p className="muted">Пока пусто. Выберите зерно в каталоге.</p>
                    ) : (
                        <>
                            <ul>
                                {cart.lines.map((line) => (
                                    <li key={line.id}>
                                        {line.image
                                            ? <img className="thumb" src={line.image} alt="" width="48" height="48" />
                                            : <span className="thumb" style={{ background: line.color }} />}
                                        <span>
                                            {line.name}
                                            <br />
                                            <small className="muted">{line.weight} · {money(line.sum)}</small>
                                        </span>
                                        <span className="qty">
                                            <button type="button" disabled={busy} aria-label="Меньше"
                                                    onClick={() => setQuantity(line.id, line.quantity - 1)}>−</button>
                                            {line.quantity}
                                            <button type="button" disabled={busy} aria-label="Больше"
                                                    onClick={() => setQuantity(line.id, line.quantity + 1)}>+</button>
                                        </span>
                                    </li>
                                ))}
                            </ul>
                            <div className="mini-cart-footer">
                                <span>
                                    <a href={cartUrl}>В корзину</a>
                                    <br />
                                    <b>{money(cart.itemsTotal)}</b>
                                </span>
                                <a className="btn" href={checkoutUrl}>Оформить</a>
                            </div>
                        </>
                    )}
                </div>
            )}
        </div>
    );
}

const mount = document.getElementById('mini-cart');
if (mount) {
    createRoot(mount).render(
        <MiniCart
            initialCart={JSON.parse(mount.dataset.cart)}
            cartUrl={mount.dataset.cartUrl}
            checkoutUrl={mount.dataset.checkoutUrl}
            icons={mount.dataset.icons}
        />
    );
}
