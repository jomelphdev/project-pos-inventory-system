EXPLAIN SELECT 
    DATE(COALESCE(r.created_at, o.created_at)) as date,
	(
        (
            SELECT COALESCE(SUM(o.cash), 0)
            FROM pos_orders as o
            LEFT JOIN pos_returns as r
            ON o.id=r.pos_order_id
            WHERE o.store_id=1 AND DATE(o.created_at)=date
        ) - 
        (
            SELECT COALESCE(SUM(r.cash), 0)
            FROM pos_returns as r
            LEFT JOIN pos_orders as o
            ON o.id=r.pos_order_id
            WHERE r.pos_order_id=o.id
        )
    ) as cash,
    (
        (
            SELECT COALESCE(SUM(o.card), 0)
            FROM pos_orders as o
            LEFT JOIN pos_returns as r
            ON o.id=r.pos_order_id
            WHERE o.store_id=1 AND DATE(o.created_at)=date
        ) - 
        (
            SELECT COALESCE(SUM(r.card), 0)
            FROM pos_returns as r
            LEFT JOIN pos_orders as o
            ON o.id=r.pos_order_id
            WHERE r.pos_order_id=o.id AND DATE(r.created_at)=date
        )
    ) as card,
    (
        (
            SELECT COALESCE(SUM(o.ebt), 0)
            FROM pos_orders as o
            LEFT JOIN pos_returns as r
            ON o.id=r.pos_order_id
            WHERE o.store_id=1 AND DATE(o.created_at)=date
        ) - 
        (
            SELECT COALESCE(SUM(r.ebt), 0)
            FROM pos_returns as r
            LEFT JOIN pos_orders as o
            ON o.id=r.pos_order_id
            WHERE r.pos_order_id=o.id
        )
    ) as ebt,
    (
        (
            SELECT COALESCE(SUM(o.sub_total), 0)
            FROM pos_orders as o
            LEFT JOIN pos_returns as r
            ON o.id=r.pos_order_id
            WHERE o.store_id=1 AND DATE(o.created_at)=date
        ) - 
        (
            SELECT COALESCE(SUM(r.sub_total), 0)
            FROM pos_returns as r
            LEFT JOIN pos_orders as o
            ON o.id=r.pos_order_id
            WHERE r.pos_order_id=o.id
        )
    ) as sub_total,
    (
        (
            SELECT COALESCE(SUM(o.tax), 0)
            FROM pos_orders as o
            LEFT JOIN pos_returns as r
            ON o.id=r.pos_order_id
            WHERE o.store_id=1 AND DATE(o.created_at)=date
        ) - 
        (
            SELECT COALESCE(SUM(r.tax), 0)
            FROM pos_returns as r
            LEFT JOIN pos_orders as o
            ON o.id=r.pos_order_id
            WHERE r.pos_order_id=o.id
        )
    ) as tax,
    (
        (
            SELECT COALESCE(SUM(o.total), 0)
            FROM pos_orders as o
            LEFT JOIN pos_returns as r
            ON o.id=r.pos_order_id
            WHERE o.store_id=1 AND DATE(o.created_at)=date
        ) - 
        (
            SELECT COALESCE(SUM(r.total), 0)
            FROM pos_returns as r
            LEFT JOIN pos_orders as o
            ON o.id=r.pos_order_id
            WHERE r.pos_order_id=o.id
        )
    ) as total,
    (
		SELECT COUNT(*)
        FROM pos_orders 
        WHERE DATE(pos_orders.created_at)=date
    ) as orders,
    (
    	SELECT COUNT(*)
        FROM pos_returns as r
        WHERE DATE(r.created_at)=date
    ) as returns
FROM pos_orders as o
LEFT JOIN pos_returns as r
ON o.id=r.pos_order_id
WHERE o.store_id=1
GROUP BY DATE(date);