CREATE TABLE `events` (
  `evt_id` bigint(11) NOT NULL,
  `evt_start` date NOT NULL,
  `evt_end` date NOT NULL,
  `evt_text` text NOT NULL,
  `evt_color` varchar(7) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `events`
  ADD PRIMARY KEY (`evt_id`),
  ADD KEY `evt_start` (`evt_start`),
  ADD KEY `evt_end` (`evt_end`);

ALTER TABLE `events`
  MODIFY `evt_id` bigint(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;